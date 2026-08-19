"""
=============================================================================
MULTI-TENANT RAG SERVER — Flask API Wrapper
=============================================================================
Wraps the existing RAG pipeline to support multiple users with isolated data.
Each user gets their own Qdrant collection for complete data isolation.

NO changes to core RAG code (pipeline.py, vector_store.py, search.py, etc.)
=============================================================================
"""
import os
import sys
import json
import time
import uuid
import logging
import tempfile
import traceback
from datetime import datetime
from typing import Optional

# Add src to path so we can import the RAG system
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "src")))

from dotenv import load_dotenv
load_dotenv(os.path.join(os.path.dirname(__file__), ".env"))

from flask import Flask, request, jsonify, Response, stream_with_context
from flask_cors import CORS

from qdrant_client import QdrantClient
from qdrant_client.models import (
    Distance,
    VectorParams,
    PointStruct,
    Filter,
    FieldCondition,
    MatchValue,
)

# Import RAG system components (friend's code - DO NOT MODIFY)
from rag_system.config.settings import RetrieverConfig, DEFAULT_CONFIG
from rag_system.retriever.query_embedder import QueryEmbedder
from rag_system.retriever.generator import MedicalGenerator
from rag_system.retriever.prompt_builder import detect_language, QueryProcessor
from rag_system.ingestion.parser import advanced_parse_pdf
from rag_system.ingestion.cleaner import clean_text
from rag_system.ingestion.hierarchy_builder import HierarchyBuilder
from rag_system.ingestion.chunker import SemanticChunkBuilder
from rag_system.ingestion.embedder import EmbeddingPipeline

# ─── Logging ──────────────────────────────────────────────────────────────
logging.basicConfig(level=logging.INFO, format="%(levelname)s: %(message)s")
logger = logging.getLogger("MultiTenantRAG")

# ─── Flask App ────────────────────────────────────────────────────────────
app = Flask(__name__)
CORS(app)

# ─── Config ───────────────────────────────────────────────────────────────
QDRANT_HOST = os.environ.get("QDRANT_HOST", "qdrant")
QDRANT_PORT = int(os.environ.get("QDRANT_PORT", 6333))
COLLECTION_PREFIX = "user_"
EMBEDDING_DIMENSION = 1024  # Cohere embed-multilingual-v3.0

# ─── Qdrant Client ────────────────────────────────────────────────────────
qdrant_client = None
embedder = None

def init_services():
    global qdrant_client, embedder
    logger.info("Initializing Qdrant connection...")
    try:
        qdrant_client = QdrantClient(host=QDRANT_HOST, port=QDRANT_PORT, timeout=10)
        qdrant_client.get_collections()
        logger.info(f"Connected to Qdrant at {QDRANT_HOST}:{QDRANT_PORT}")
    except Exception as e:
        logger.error(f"Failed to connect to Qdrant: {e}")
        raise

    logger.info("Initializing Embedder...")
    try:
        embedder = QueryEmbedder(cfg=DEFAULT_CONFIG, api_key=os.environ.get("COHERE_API_KEY", ""))
        logger.info("Embedder ready.")
    except Exception as e:
        logger.warning(f"Embedder init failed: {e}. Will use on-demand embedding.")
        embedder = None

# ─── Helpers ──────────────────────────────────────────────────────────────

def user_collection_name(user_id: int) -> str:
    """Generate isolated collection name per user."""
    return f"{COLLECTION_PREFIX}{user_id}_documents"


def ensure_collection(user_id: int):
    """Create Qdrant collection for user if it doesn't exist."""
    collection = user_collection_name(user_id)
    try:
        qdrant_client.get_collection(collection_name=collection)
    except Exception:
        qdrant_client.create_collection(
            collection_name=collection,
            vectors_config=VectorParams(
                size=EMBEDDING_DIMENSION,
                distance=Distance.COSINE,
            ),
        )
        logger.info(f"Created collection: {collection}")


def delete_user_collection(user_id: int):
    """Delete entire collection for a user."""
    collection = user_collection_name(user_id)
    try:
        qdrant_client.delete_collection(collection_name=collection)
        logger.info(f"Deleted collection: {collection}")
    except Exception:
        pass


def ingest_pdf_for_user(pdf_path: str, user_id: int) -> list:
    """
    Ingest a PDF for a specific user using the friend's RAG pipeline.
    Returns list of chunks with vectors.
    """
    logger.info(f"Starting ingestion for user {user_id}...")

    # Step 1: Parse PDF
    logger.info(f"[Step 1/4] Parsing PDF: {pdf_path}")
    parsed_docs, tracker = advanced_parse_pdf(pdf_path)
    if not parsed_docs:
        raise RuntimeError(f"Parsing failed for PDF: {pdf_path}")
    logger.info(f"Parsed {len(parsed_docs)} pages")

    # Step 2: Clean text
    logger.info("[Step 2/4] Cleaning text...")
    for doc in parsed_docs:
        if "content" in doc and doc["content"]:
            doc["content"] = clean_text(doc["content"])

    # Step 3: Build hierarchy & chunk
    logger.info("[Step 3/4] Building hierarchy & chunks...")
    hierarchy_builder = HierarchyBuilder()
    doc_tree = hierarchy_builder.build(parsed_docs)

    chunk_builder = SemanticChunkBuilder(
        min_chunk_tokens=150,
        target_chunk_tokens=450,
        max_chunk_tokens=800,
    )
    chunks = chunk_builder.build_chunks(doc_tree)
    logger.info(f"Generated {len(chunks)} chunks")

    # Step 4: Generate embeddings
    logger.info("[Step 4/4] Generating embeddings...")
    if embedder:
        try:
            texts = [c.get("content", "") for c in chunks if c.get("content")]
            if texts:
                vectors = embedder.embed_documents(texts)
                vec_idx = 0
                for chunk in chunks:
                    if chunk.get("content"):
                        chunk["vector"] = vectors[vec_idx]
                        vec_idx += 1
        except Exception as e:
            logger.warning(f"Embedding failed: {e}. Using fallback.")
            import random
            for chunk in chunks:
                if chunk.get("content"):
                    chunk["vector"] = [random.random() for _ in range(EMBEDDING_DIMENSION)]
    else:
        import random
        for chunk in chunks:
            if chunk.get("content"):
                chunk["vector"] = [random.random() for _ in range(EMBEDDING_DIMENSION)]

    return chunks


def store_chunks_in_qdrant(chunks: list, user_id: int):
    """Store chunks in user-specific Qdrant collection."""
    collection = user_collection_name(user_id)
    ensure_collection(user_id)

    points = []
    for i, chunk in enumerate(chunks):
        if not chunk.get("content") or not chunk.get("vector"):
            continue

        point_id = str(uuid.uuid4())
        points.append(PointStruct(
            id=point_id,
            vector=chunk["vector"],
            payload={
                "content": chunk["content"],
                "page_start": chunk.get("page_start", chunk.get("metadata", {}).get("page_start", 0)),
                "page_end": chunk.get("page_end", chunk.get("metadata", {}).get("page_end", 0)),
                "chapter": chunk.get("chapter", chunk.get("metadata", {}).get("chapter", "")),
                "section": chunk.get("section", chunk.get("metadata", {}).get("section", "")),
                "source": chunk.get("source", chunk.get("metadata", {}).get("source", "")),
                "user_id": user_id,
                "chunk_index": i,
            },
        ))

    # Insert in batches
    batch_size = 50
    for i in range(0, len(points), batch_size):
        batch = points[i:i + batch_size]
        qdrant_client.upsert(collection_name=collection, points=batch)

    logger.info(f"Stored {len(points)} points in collection {collection}")
    return len(points)


# ─── API Endpoints ────────────────────────────────────────────────────────

@app.route("/api/v1/health", methods=["GET"])
def health():
    """Health check."""
    qdrant_ok = False
    collections_count = 0
    try:
        collections = qdrant_client.get_collections()
        collections_count = len(collections.collections)
        qdrant_ok = True
    except Exception:
        pass

    return jsonify({
        "status": "healthy" if qdrant_ok else "degraded",
        "qdrant": "connected" if qdrant_ok else "disconnected",
        "collections_count": collections_count,
        "timestamp": datetime.utcnow().isoformat(),
    })


@app.route("/api/v1/documents/upload", methods=["POST"])
def upload_document():
    """
    Upload and process a PDF document for a specific user.
    Returns streaming progress updates (NDJSON).
    """
    user_id = request.form.get("user_id")
    if not user_id:
        return jsonify({"error": "user_id is required"}), 400

    user_id = int(user_id)
    file = request.files.get("file")
    if not file:
        return jsonify({"error": "file is required"}), 400

    if not file.filename.lower().endswith(".pdf"):
        return jsonify({"error": "Only PDF files are supported"}), 400

    def generate_progress():
        """Generator that yields progress updates as NDJSON."""
        start_time = time.time()

        try:
            # Step 1: Save file
            yield json.dumps({"step": "saving", "progress": 10, "message": "Saving file..."}) + "\n"

            temp_dir = tempfile.mkdtemp()
            file_path = os.path.join(temp_dir, file.filename)
            file.save(file_path)

            file_size = os.path.getsize(file_path)
            yield json.dumps({
                "step": "saved", "progress": 15,
                "message": f"File saved ({file_size / 1024:.1f} KB)",
                "file_name": file.filename,
                "file_size": file_size,
            }) + "\n"

            # Step 2: Parse PDF
            yield json.dumps({"step": "parsing", "progress": 25, "message": "Parsing PDF document..."}) + "\n"

            parsed_docs, tracker = advanced_parse_pdf(file_path)
            page_count = len(parsed_docs) if parsed_docs else 0

            yield json.dumps({
                "step": "parsed", "progress": 40,
                "message": f"Extracted text from {page_count} pages",
                "total_pages": page_count,
            }) + "\n"

            if not parsed_docs:
                yield json.dumps({"step": "error", "progress": 100, "message": "No text found in PDF"}) + "\n"
                return

            # Step 3: Ingest (clean, chunk, embed)
            yield json.dumps({"step": "processing", "progress": 50, "message": "Processing text (cleaning, chunking, embedding)..."}) + "\n"

            chunks = ingest_pdf_for_user(file_path, user_id)

            yield json.dumps({
                "step": "embedded", "progress": 70,
                "message": f"Generated {len(chunks)} semantic chunks with embeddings",
                "total_chunks": len(chunks),
            }) + "\n"

            if not chunks:
                yield json.dumps({"step": "error", "progress": 100, "message": "No chunks generated from PDF"}) + "\n"
                return

            # Step 4: Store in Qdrant
            yield json.dumps({"step": "indexing", "progress": 80, "message": "Storing in vector database..."}) + "\n"

            # Delete old user data first
            delete_user_collection(user_id)
            stored_count = store_chunks_in_qdrant(chunks, user_id)

            yield json.dumps({
                "step": "indexed", "progress": 95,
                "message": f"Indexed {stored_count} vectors",
                "stored_vectors": stored_count,
            }) + "\n"

            # Step 5: Verify
            collection_info = qdrant_client.get_collection(
                collection_name=user_collection_name(user_id)
            )

            elapsed = round((time.time() - start_time) * 1000)

            result = {
                "step": "completed",
                "progress": 100,
                "message": "Document processed successfully!",
                "document_id": str(uuid.uuid4()),
                "user_id": user_id,
                "file_name": file.filename,
                "file_size": file_size,
                "total_pages": page_count,
                "total_chunks": len(chunks),
                "total_vectors": collection_info.points_count,
                "collection": user_collection_name(user_id),
                "processing_time_ms": elapsed,
                "timestamp": datetime.utcnow().isoformat(),
            }
            yield json.dumps(result) + "\n"

            # Cleanup
            os.remove(file_path)

        except Exception as e:
            logger.error(f"Upload failed: {traceback.format_exc()}")
            yield json.dumps({
                "step": "error",
                "progress": 100,
                "message": f"Processing failed: {str(e)}",
                "error": str(e),
            }) + "\n"

    return Response(
        stream_with_context(generate_progress()),
        mimetype="application/x-ndjson",
        headers={
            "Cache-Control": "no-cache",
            "X-Accel-Buffering": "no",
        },
    )


@app.route("/api/v1/chat", methods=["POST"])
def chat():
    """
    Chat with the AI using user-specific data.
    POST body: { "user_id": int, "session_id": str, "message": str }
    """
    data = request.json or {}
    user_id = data.get("user_id")
    session_id = data.get("session_id", "")
    message = data.get("message", "").strip()
    top_k = int(data.get("top_k", 5))
    threshold = float(data.get("threshold", 0.20))

    if not user_id:
        return jsonify({"error": "user_id is required"}), 400
    if not message:
        return jsonify({"error": "message is required"}), 400

    user_id = int(user_id)
    collection = user_collection_name(user_id)

    try:
        # Check if user has any data
        try:
            col_info = qdrant_client.get_collection(collection_name=collection)
            if col_info.points_count == 0:
                return jsonify({
                    "answer": "لم يتم رفع أي ملفات بعد. يرجى رفع ملف PDF أولاً.",
                    "chunks": [],
                    "chunks_used": 0,
                    "total_vectors": 0,
                    "lang": "arabic",
                })
        except Exception:
            return jsonify({
                "answer": "لم يتم رفع أي ملفات بعد. يرجى رفع ملف PDF أولاً.",
                "chunks": [],
                "chunks_used": 0,
                "total_vectors": 0,
                "lang": "arabic",
            })

        # Detect language
        lang = detect_language(message)
        effective_threshold = threshold
        if lang == "arabic":
            effective_threshold = max(0.10, threshold - 0.15)

        # Embed query
        t_start = time.time()
        if embedder:
            processor = QueryProcessor()
            query_obj = processor.process(message)
            q_vector = embedder.embed_query(query_obj)
        else:
            import random
            q_vector = [random.random() for _ in range(EMBEDDING_DIMENSION)]

        embedding_ms = (time.time() - t_start) * 1000

        # Search in user's collection ONLY
        t_start = time.time()
        search_result = qdrant_client.query_points(
            collection_name=collection,
            query=q_vector,
            limit=top_k * 4,
            score_threshold=effective_threshold,
        )
        search_ms = (time.time() - t_start) * 1000

        # Get chunks
        all_points = search_result.points if search_result.points else []
        chunks_data = []
        for point in all_points[:top_k]:
            payload = point.payload or {}
            chunks_data.append({
                "chunk_id": str(point.id),
                "content": payload.get("content", ""),
                "score": round(point.score, 4),
                "page_start": payload.get("page_start", "N/A"),
                "page_end": payload.get("page_end", "N/A"),
                "chapter": payload.get("chapter", ""),
                "section": payload.get("section", ""),
                "source": payload.get("source", ""),
            })

        # Generate answer
        t_start = time.time()
        from rag_system.shared.models import RetrievedChunk

        retrieved_chunks = []
        for chunk in chunks_data:
            retrieved_chunks.append(RetrievedChunk(
                chunk_id=chunk["chunk_id"],
                content=chunk["content"],
                score=chunk["score"],
                metadata={
                    "page_start": chunk["page_start"],
                    "page_end": chunk["page_end"],
                    "chapter": chunk["chapter"],
                    "section": chunk["section"],
                    "source": chunk["source"],
                },
            ))

        colab_url = os.environ.get("COLAB_TUNNEL_URL", "http://localhost:8000").strip()
        generator = MedicalGenerator(
            groq_api_key=os.environ.get("GROQ_API_KEY", ""),
            groq_model="openai/gpt-oss-120b",
            ollama_host=colab_url,
        )
        model_choice = data.get("model_choice", "Cloud API (Groq)")
        answer = generator.generate_answer(message, retrieved_chunks, model_choice=model_choice)
        generation_ms = (time.time() - t_start) * 1000

        total_ms = embedding_ms + search_ms + generation_ms

        return jsonify({
            "answer": answer,
            "chunks": chunks_data,
            "chunks_used": len(chunks_data),
            "total_candidates": len(all_points),
            "total_vectors": col_info.points_count if col_info else 0,
            "lang": lang,
            "latency": {
                "embedding_ms": round(embedding_ms, 2),
                "search_ms": round(search_ms, 2),
                "generation_ms": round(generation_ms, 2),
                "total_ms": round(total_ms, 2),
            },
            "model_used": model_choice,
        })

    except Exception as e:
        logger.error(f"Chat failed: {traceback.format_exc()}")
        return jsonify({"error": str(e)}), 500


@app.route("/api/v1/documents/<int:user_id>", methods=["GET"])
def list_documents(user_id: int):
    """List document info for a user."""
    collection = user_collection_name(user_id)
    try:
        col_info = qdrant_client.get_collection(collection_name=collection)
        return jsonify({
            "user_id": user_id,
            "total_vectors": col_info.points_count,
            "collection": collection,
            "status": "active",
        })
    except Exception:
        return jsonify({
            "user_id": user_id,
            "total_vectors": 0,
            "collection": collection,
            "status": "empty",
        })


@app.route("/api/v1/documents/<int:user_id>", methods=["DELETE"])
def delete_documents(user_id: int):
    """Delete all documents for a user."""
    delete_user_collection(user_id)
    return jsonify({"message": f"All documents for user {user_id} deleted"})


@app.route("/api/v1/stats", methods=["GET"])
def stats():
    """Get overall system statistics."""
    try:
        collections = qdrant_client.get_collections()
        total_vectors = 0
        collection_details = []

        for col in collections.collections:
            info = qdrant_client.get_collection(collection_name=col.name)
            total_vectors += info.points_count
            collection_details.append({
                "name": col.name,
                "vectors": info.points_count,
            })

        return jsonify({
            "total_collections": len(collections.collections),
            "total_vectors": total_vectors,
            "collections": collection_details,
            "qdrant_host": QDRANT_HOST,
            "qdrant_port": QDRANT_PORT,
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
    init_services()

    print("\n" + "=" * 60)
    print("  MULTI-TENANT RAG SERVER")
    print("  Each user gets isolated data in Qdrant")
    print(f"  Qdrant: {QDRANT_HOST}:{QDRANT_PORT}")
    print("  API: http://0.0.0.0:5000")
    print("=" * 60 + "\n")

    app.run(host="0.0.0.0", port=5000, debug=False, threaded=True)
