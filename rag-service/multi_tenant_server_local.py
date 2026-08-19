"""
=============================================================================
MULTI-TENANT RAG SERVER — LOCAL VERSION (No Docker)
=============================================================================
Wraps the friend's RAG system for multi-tenant use.
Each user gets isolated data via separate Qdrant collections.
Uses the friend's actual ingestion pipeline (Parse → Clean → Chunk → Embed → Index).
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
import shutil
from datetime import datetime

# Setup path to friend's RAG code
RAG_SRC = os.path.join(os.path.dirname(__file__), "src")
sys.path.insert(0, os.path.abspath(RAG_SRC))

from dotenv import load_dotenv
load_dotenv(os.path.join(os.path.dirname(__file__), ".env"))

from flask import Flask, request, jsonify, Response, stream_with_context
from flask_cors import CORS

from qdrant_client import QdrantClient
from qdrant_client.models import Distance, VectorParams, PointStruct

# Import friend's RAG system components (DO NOT MODIFY)
from rag_system.config.settings import RetrieverConfig, DEFAULT_CONFIG
from rag_system.ingestion.parser import advanced_parse_pdf
from rag_system.ingestion.cleaner import clean_text
from rag_system.ingestion.hierarchy_builder import HierarchyBuilder
from rag_system.ingestion.chunker import SemanticChunkBuilder, export_chunks
from rag_system.ingestion.embedder import EmbeddingPipeline
from rag_system.retriever.prompt_builder import detect_language, QueryProcessor

# ─── Logging ──────────────────────────────────────────────────────────────
logging.basicConfig(level=logging.INFO, format="%(levelname)s: %(message)s")
logger = logging.getLogger("MultiTenantRAG")

# ─── Flask App ────────────────────────────────────────────────────────────
app = Flask(__name__)
CORS(app)

# ─── Config ───────────────────────────────────────────────────────────────
# Detect embedding dimension from the embedder
try:
    from rag_system.embeddings.local import FastEmbedEmbedder
    _temp_embedder = FastEmbedEmbedder()
    EMBEDDING_DIMENSION = _temp_embedder.dimension
    logger.info(f"Using FastEmbed embeddings (dimension: {EMBEDDING_DIMENSION})")
except Exception:
    EMBEDDING_DIMENSION = 384
    logger.warning(f"Could not detect embedding dimension, defaulting to {EMBEDDING_DIMENSION}")

# Override DEFAULT_CONFIG to match FastEmbed instead of Cohere
DEFAULT_CONFIG.embedding_dimension = EMBEDDING_DIMENSION
DEFAULT_CONFIG.top_k_initial = 20
DEFAULT_CONFIG.top_k_final = 5
DEFAULT_CONFIG.similarity_threshold = 0.15  # Lower threshold for better recall
DEFAULT_CONFIG.distance_metric = "cosine"

COLLECTION_PREFIX = "user_"

# Use embedded Qdrant (no Docker needed)
QDRANT_PATH = os.path.join(os.path.dirname(__file__), "data", "qdrant_db")
os.makedirs(QDRANT_PATH, exist_ok=True)

qdrant_client = None


def init_services():
    global qdrant_client
    logger.info("Initializing Qdrant (embedded mode)...")
    try:
        qdrant_client = QdrantClient(path=QDRANT_PATH)
        logger.info(f"Qdrant ready at {QDRANT_PATH}")
    except Exception as e:
        logger.error(f"Failed to init Qdrant: {e}")
        raise


def user_collection_name(user_id: int) -> str:
    return f"{COLLECTION_PREFIX}{user_id}_documents"


def ensure_collection(user_id: int):
    collection = user_collection_name(user_id)
    try:
        qdrant_client.get_collection(collection_name=collection)
    except Exception:
        qdrant_client.create_collection(
            collection_name=collection,
            vectors_config=VectorParams(size=EMBEDDING_DIMENSION, distance=Distance.COSINE),
        )
        logger.info(f"Created collection: {collection}")


def delete_user_collection(user_id: int):
    collection = user_collection_name(user_id)
    try:
        qdrant_client.delete_collection(collection_name=collection)
    except Exception:
        pass


def get_user_data_dir(user_id: int) -> str:
    """Get per-user data directory for intermediate files."""
    d = os.path.join(os.path.dirname(__file__), "data", f"user_{user_id}")
    os.makedirs(d, exist_ok=True)
    os.makedirs(os.path.join(d, "chunks"), exist_ok=True)
    os.makedirs(os.path.join(d, "embeddings"), exist_ok=True)
    return d


def run_full_ingestion_for_user(pdf_path: str, user_id: int, progress_callback=None) -> dict:
    """
    Run the friend's full ingestion pipeline for a specific user.
    Uses temporary directories so each user's data is isolated.
    
    Steps: Parse → Clean → Hierarchy → Chunk → Embed → Index to Qdrant
    """
    start_time = time.time()
    user_data_dir = get_user_data_dir(user_id)
    chunks_dir = os.path.join(user_data_dir, "chunks")
    embeddings_dir = os.path.join(user_data_dir, "embeddings")

    def emit_progress(step, progress, message):
        if progress_callback:
            progress_callback(step, progress, message)

    # ─── Step 1: Parse PDF ──────────────────────────────────────────────
    emit_progress("parsing", 20, "Parsing PDF document...")
    parsed_docs, tracker = advanced_parse_pdf(pdf_path)
    if not parsed_docs:
        raise RuntimeError(f"Parsing failed for: {pdf_path}")
    emit_progress("parsed", 35, f"Parsed {len(parsed_docs)} pages successfully.")
    logger.info(f"Step 1 done: {len(parsed_docs)} pages parsed.")

    # ─── Step 2: Clean Text ─────────────────────────────────────────────
    emit_progress("cleaning", 40, "Cleaning text contents...")
    for doc in parsed_docs:
        if "content" in doc and doc["content"]:
            doc["content"] = clean_text(doc["content"])
    logger.info("Step 2 done: Text cleaned.")

    # ─── Step 3: Build Hierarchy ────────────────────────────────────────
    emit_progress("hierarchy", 45, "Building document hierarchy...")
    hierarchy_builder = HierarchyBuilder()
    doc_tree = hierarchy_builder.build(parsed_docs)
    logger.info("Step 3 done: Hierarchy built.")

    # ─── Step 4: Chunk Document ─────────────────────────────────────────
    emit_progress("chunking", 50, "Constructing semantic chunks...")
    chunk_builder = SemanticChunkBuilder(
        min_chunk_tokens=100,
        target_chunk_tokens=350,
        max_chunk_tokens=600
    )
    chunks = chunk_builder.build_chunks(doc_tree)
    # Export chunks to user-specific directory
    chunks_json_path = os.path.join(chunks_dir, "chunks.json")
    chunks_jsonl_path = os.path.join(chunks_dir, "chunks.jsonl")
    export_chunks(chunks, json_path=chunks_json_path, jsonl_path=chunks_jsonl_path)
    emit_progress("chunked", 60, f"Generated {len(chunks)} semantic chunks.")
    logger.info(f"Step 4 done: {len(chunks)} chunks generated.")

    # ─── Step 5: Generate Embeddings ────────────────────────────────────
    emit_progress("embedding", 65, f"Generating embeddings for {len(chunks)} chunks...")

    # The friend's EmbeddingPipeline imports DEFAULT_CHUNKS_JSON at module level,
    # so we need to write chunks to the default path it expects, then move the output.
    from rag_system.config import config as rag_config
    default_chunks_dir = os.path.dirname(rag_config.DEFAULT_CHUNKS_JSON)
    default_embeddings_dir = os.path.dirname(rag_config.DEFAULT_EMBEDDINGS_JSON)
    os.makedirs(default_chunks_dir, exist_ok=True)
    os.makedirs(default_embeddings_dir, exist_ok=True)

    # Backup existing files at the default path (if any)
    backup_chunks = None
    backup_embeddings = None
    if os.path.exists(rag_config.DEFAULT_CHUNKS_JSON):
        backup_chunks = rag_config.DEFAULT_CHUNKS_JSON + ".bak"
        shutil.copy2(rag_config.DEFAULT_CHUNKS_JSON, backup_chunks)
    if os.path.exists(rag_config.DEFAULT_EMBEDDINGS_JSON):
        backup_embeddings = rag_config.DEFAULT_EMBEDDINGS_JSON + ".bak"
        shutil.copy2(rag_config.DEFAULT_EMBEDDINGS_JSON, backup_embeddings)

    try:
        # Write user's chunks to the default location so EmbeddingPipeline can find them
        shutil.copy2(chunks_json_path, rag_config.DEFAULT_CHUNKS_JSON)
        # Also copy the JSONL
        chunks_jsonl_default = rag_config.DEFAULT_CHUNKS_JSONL if hasattr(rag_config, 'DEFAULT_CHUNKS_JSONL') else os.path.join(default_chunks_dir, "chunks.jsonl")
        if os.path.exists(chunks_jsonl_path):
            shutil.copy2(chunks_jsonl_path, chunks_jsonl_default)

        embedding_pipeline = EmbeddingPipeline()
        embedded_records = embedding_pipeline.run()

        # Now copy the generated embeddings back to user-specific directory
        if os.path.exists(rag_config.DEFAULT_EMBEDDINGS_JSON):
            shutil.copy2(rag_config.DEFAULT_EMBEDDINGS_JSON, os.path.join(embeddings_dir, "embeddings.json"))
    finally:
        # Restore original files at the default path
        if backup_chunks and os.path.exists(backup_chunks):
            shutil.move(backup_chunks, rag_config.DEFAULT_CHUNKS_JSON)
        elif os.path.exists(rag_config.DEFAULT_CHUNKS_JSON):
            os.remove(rag_config.DEFAULT_CHUNKS_JSON)
        if backup_embeddings and os.path.exists(backup_embeddings):
            shutil.move(backup_embeddings, rag_config.DEFAULT_EMBEDDINGS_JSON)
        elif os.path.exists(rag_config.DEFAULT_EMBEDDINGS_JSON):
            os.remove(rag_config.DEFAULT_EMBEDDINGS_JSON)

    emit_progress("embedded", 80, f"Generated {len(embedded_records)} embeddings.")
    logger.info(f"Step 5 done: {len(embedded_records)} embeddings generated.")

    # ─── Step 6: Index to Qdrant ────────────────────────────────────────
    emit_progress("indexing", 85, "Indexing to vector database...")
    delete_user_collection(user_id)
    ensure_collection(user_id)

    # Load embeddings and index them
    embeddings_json_path = os.path.join(embeddings_dir, "embeddings.json")
    with open(embeddings_json_path, "r", encoding="utf-8") as f:
        embeddings_data = json.load(f)

    batch_size = 50
    total_points = len(embeddings_data)
    for i in range(0, total_points, batch_size):
        batch = embeddings_data[i:i + batch_size]
        points = []
        for item in batch:
            embedding = item.get("embedding", [])
            content = item.get("content", "")
            metadata = item.get("metadata", {})
            chunk_id = item.get("chunk_id", str(uuid.uuid4()))

            if embedding and len(embedding) == EMBEDDING_DIMENSION:
                points.append(PointStruct(
                    id=str(uuid.uuid4()),
                    vector=embedding,
                    payload={
                        "chunk_id": chunk_id,
                        "content": content,
                        "metadata": metadata,
                        "user_id": user_id,
                        "table_references": item.get("table_references", []),
                        "figure_references": item.get("figure_references", []),
                    },
                ))

        if points:
            qdrant_client.upsert(
                collection_name=user_collection_name(user_id),
                points=points
            )

        progress = 85 + int((i + len(batch)) / total_points * 13)
        emit_progress("indexing", progress, f"Indexed {min(i + batch_size, total_points)}/{total_points} chunks")

    # Get final collection info
    col_info = qdrant_client.get_collection(collection_name=user_collection_name(user_id))
    elapsed = round((time.time() - start_time) * 1000)

    # Build quality metrics
    quality_metrics = {
        "total_pages_parsed": len(parsed_docs),
        "pages_with_tables": sum(1 for d in parsed_docs if d.get("metadata", {}).get("has_tables")),
        "pages_with_figures": sum(1 for d in parsed_docs if d.get("metadata", {}).get("has_tables") is False and "figure" in str(d.get("content", "")).lower()),
        "ocr_fallbacks": tracker.ocr_fallbacks if hasattr(tracker, 'ocr_fallbacks') else 0,
        "tables_extracted": tracker.tables_extracted if hasattr(tracker, 'tables_extracted') else 0,
        "figures_extracted": tracker.figures_extracted if hasattr(tracker, 'figures_extracted') else 0,
        "columns_processed": tracker.columns_processed if hasattr(tracker, 'columns_processed') else 0,
        "parsing_time_ms": round((tracker.elapsed_time() if hasattr(tracker, 'elapsed_time') else 0) * 1000),
    }

    result = {
        "step": "completed",
        "progress": 100,
        "message": "Document processed successfully!",
        "document_id": str(uuid.uuid4()),
        "user_id": user_id,
        "file_name": os.path.basename(pdf_path),
        "file_size": os.path.getsize(pdf_path),
        "total_chunks": len(chunks),
        "total_embeddings": len(embedded_records),
        "total_vectors": col_info.points_count,
        "collection": user_collection_name(user_id),
        "processing_time_ms": elapsed,
        "quality_metrics": quality_metrics,
    }

    logger.info(f"Full ingestion completed for user {user_id}: {len(chunks)} chunks, {col_info.points_count} vectors, {elapsed}ms")
    return result


# ─── API Endpoints ────────────────────────────────────────────────────────

@app.route("/api/v1/health", methods=["GET"])
def health():
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
        "server_mode": "local",
    })


@app.route("/api/v1/documents/upload", methods=["POST"])
def upload_document():
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
        try:
            yield json.dumps({"step": "saving", "progress": 5, "message": "Saving uploaded file..."}) + "\n"

            temp_dir = tempfile.mkdtemp()
            file_path = os.path.join(temp_dir, file.filename)
            file.save(file_path)
            file_size = os.path.getsize(file_path)

            yield json.dumps({"step": "saved", "progress": 10, "message": f"File saved ({file_size / 1024:.1f} KB)"}) + "\n"

            # Run the full friend's ingestion pipeline
            def on_progress(step, progress, message):
                # Map friend's pipeline progress to our upload progress (10-100%)
                mapped_progress = 10 + int(progress * 0.9)
                yield json.dumps({"step": step, "progress": mapped_progress, "message": message}) + "\n"

            # Run ingestion and collect results
            result = run_full_ingestion_for_user(file_path, user_id)

            # Yield the final result
            yield json.dumps(result) + "\n"

            # Cleanup temp file
            shutil.rmtree(temp_dir, ignore_errors=True)

        except Exception as e:
            logger.error(f"Upload failed: {traceback.format_exc()}")
            yield json.dumps({"step": "error", "progress": 100, "message": f"Failed: {str(e)}"}) + "\n"

    return Response(
        stream_with_context(generate_progress()),
        mimetype="application/x-ndjson",
        headers={"Cache-Control": "no-cache", "X-Accel-Buffering": "no"},
    )


@app.route("/api/v1/chat", methods=["POST"])
def chat():
    data = request.json or {}
    user_id = data.get("user_id")
    message = data.get("message", "").strip()
    top_k = int(data.get("top_k", 5))

    if not user_id or not message:
        return jsonify({"error": "user_id and message are required"}), 400

    user_id = int(user_id)
    collection = user_collection_name(user_id)

    try:
        # Check if user has data
        try:
            col_info = qdrant_client.get_collection(collection_name=collection)
            if col_info.points_count == 0:
                return jsonify({
                    "answer": "لم يتم رفع أي ملفات بعد. يرجى رفع ملف PDF أولاً.",
                    "chunks": [],
                    "chunks_used": 0,
                    "total_vectors": 0,
                })
        except Exception:
            return jsonify({
                "answer": "لم يتم رفع أي ملفات بعد. يرجى رفع ملف PDF أولاً.",
                "chunks": [],
                "chunks_used": 0,
                "total_vectors": 0,
            })

        # Use friend's query processor for preprocessing
        processor = QueryProcessor()
        query_obj = processor.process(message)

        # Use friend's embedder for query embedding
        from rag_system.retriever.query_embedder import QueryEmbedder
        embedder = QueryEmbedder(cfg=DEFAULT_CONFIG)
        q_vector = embedder.embed_query(query_obj)

        # Search in user's collection — retrieve MORE candidates, then rerank
        initial_k = top_k * 4  # Get 4x candidates for better reranking
        search_result = qdrant_client.query_points(
            collection_name=collection,
            query=q_vector,
            limit=initial_k,
        )

        all_points = search_result.points if search_result.points else []

        # Rerank: sort by score descending, then deduplicate by content similarity
        seen_content_prefixes = set()
        chunks_data = []
        for point in sorted(all_points, key=lambda p: p.score, reverse=True):
            payload = point.payload or {}
            content = payload.get("content", "")
            # Deduplicate: skip if first 100 chars already seen
            content_prefix = content[:100].strip().lower()
            if content_prefix in seen_content_prefixes or not content_prefix:
                continue
            seen_content_prefixes.add(content_prefix)
            chunks_data.append({
                "chunk_id": payload.get("chunk_id", str(point.id)),
                "content": content,
                "score": round(point.score, 4),
                "metadata": payload.get("metadata", {}),
                "table_references": payload.get("table_references", []),
                "figure_references": payload.get("figure_references", []),
            })
            if len(chunks_data) >= top_k:
                break

        # Build structured sources
        sources = []
        context_parts = []
        for i, chunk in enumerate(chunks_data, 1):
            meta = chunk.get("metadata", {})
            source = {
                "index": i,
                "chunk_id": chunk["chunk_id"],
                "content": chunk["content"][:500],
                "score": chunk["score"],
                "page_start": meta.get("page_start", "?"),
                "page_end": meta.get("page_end", "?"),
                "chapter": meta.get("chapter", ""),
                "section": meta.get("section", ""),
                "subsection": meta.get("subsection", ""),
                "document_title": meta.get("document_title", ""),
                "token_count": meta.get("token_count", 0),
                "table_references": chunk.get("table_references", []),
            }
            sources.append(source)
            context_parts.append(f"[Source {i}] ({meta.get('chapter', '')} > {meta.get('section', '')})\n{chunk['content']}")

        context = "\n\n---\n\n".join(context_parts)

        # Build answer with actual content from sources
        if context.strip():
            answer_parts = []
            for src in sources:
                ch = src['chapter'] if src['chapter'] and src['chapter'] != 'Unknown' else ''
                sec = src['section'] if src['section'] else ''
                page = f"p.{src['page_start']}" if src['page_start'] != '?' else ''
                location = " > ".join(filter(None, [ch, sec, page]))
                score_pct = round(src['score'] * 100, 1)
                answer_parts.append(
                    f"**[Source {src['index']}]** ({location}) — Relevance: {score_pct}%\n\n"
                    f"{src['content']}"
                )
            answer = "\n\n---\n\n".join(answer_parts)
        else:
            answer = "No relevant information found in the uploaded document. Please make sure you have uploaded a PDF file."

        return jsonify({
            "answer": answer,
            "sources": sources,
            "chunks_used": len(chunks_data),
            "total_vectors": col_info.points_count,
            "lang": detect_language(message),
            "retrieval_info": {
                "top_k": top_k,
                "similarity_threshold": DEFAULT_CONFIG.similarity_threshold,
                "collection": collection,
            }
        })

    except Exception as e:
        logger.error(f"Chat failed: {traceback.format_exc()}")
        return jsonify({"error": str(e)}), 500


@app.route("/api/v1/documents/<int:user_id>", methods=["GET"])
def list_documents(user_id: int):
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
    delete_user_collection(user_id)
    # Also cleanup user data directory
    user_data_dir = os.path.join(os.path.dirname(__file__), "data", f"user_{user_id}")
    if os.path.exists(user_data_dir):
        shutil.rmtree(user_data_dir, ignore_errors=True)
    return jsonify({"message": f"All documents for user {user_id} deleted"})


@app.route("/api/v1/stats", methods=["GET"])
def stats():
    try:
        collections = qdrant_client.get_collections()
        total_vectors = 0
        details = []
        for col in collections.collections:
            info = qdrant_client.get_collection(collection_name=col.name)
            total_vectors += info.points_count
            details.append({"name": col.name, "vectors": info.points_count})
        return jsonify({
            "total_collections": len(collections.collections),
            "total_vectors": total_vectors,
            "collections": details,
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route("/api/v1/user/<int:user_id>/stats", methods=["GET"])
def user_stats(user_id: int):
    """Get stats for a specific user."""
    collection = user_collection_name(user_id)
    try:
        col_info = qdrant_client.get_collection(collection_name=collection)
        return jsonify({
            "user_id": user_id,
            "collection": collection,
            "total_vectors": col_info.points_count,
            "status": "active",
        })
    except Exception:
        return jsonify({
            "user_id": user_id,
            "collection": collection,
            "total_vectors": 0,
            "status": "empty",
        })


@app.route("/api/v1/user/<int:user_id>/metrics", methods=["GET"])
def user_metrics(user_id: int):
    """
    Run retrieval quality evaluation for a user's collection.
    Calculates Recall@K, Precision@K, F1, MRR, Hit Rate, nDCG, MAP, etc.
    Uses random query sampling against the user's indexed chunks.
    """
    collection = user_collection_name(user_id)
    try:
        col_info = qdrant_client.get_collection(collection_name=collection)
        total_vectors = col_info.points_count
    except Exception:
        return jsonify({"error": "No data found for this user"}), 404

    if total_vectors == 0:
        return jsonify({"error": "Collection is empty"}), 404

    try:
        from rag_system.retriever.metrics import (
            calculate_recall_at_k,
            calculate_precision_at_k,
            calculate_f1_score,
            calculate_mrr,
            calculate_hit_rate,
            calculate_ndcg_at_k,
            calculate_ap,
        )
        from rag_system.retriever.query_embedder import QueryEmbedder

        # Get all chunks from the collection for self-evaluation
        all_points = []
        offset = None
        while True:
            result = qdrant_client.scroll(
                collection_name=collection,
                limit=100,
                offset=offset,
                with_payload=True,
                with_vectors=False,
            )
            points, offset = result
            all_points.extend(points)
            if offset is None:
                break

        if len(all_points) < 3:
            return jsonify({"error": "Not enough data for evaluation (need at least 3 chunks)"}), 400

        # Use each chunk's content as a benchmark query
        # The ground truth is the chunk itself
        import random
        sample_size = min(10, len(all_points))
        sample_points = random.sample(all_points, sample_size)

        embedder = QueryEmbedder(cfg=DEFAULT_CONFIG)

        recalls_1, recalls_3, recalls_5, recalls_10 = [], [], [], []
        precisions_1, precisions_3, precisions_5, precisions_10 = [], [], [], []
        f1s_5, f1s_10 = [], []
        mrrs, hit_rates = [], []
        ndcgs_3, ndcgs_5, ndcgs_10 = [], [], []
        aps = []
        latencies = []

        for point in sample_points:
            chunk_id = point.payload.get("chunk_id", str(point.id))
            content = point.payload.get("content", "")
            if not content:
                continue

            # Use first 100 chars as query
            query_text = content[:100].strip()
            if not query_text:
                continue

            try:
                from rag_system.retriever.prompt_builder import QueryProcessor
                processor = QueryProcessor()
                query_obj = processor.process(query_text)
                q_vector = embedder.embed_query(query_obj)
            except Exception:
                continue

            t0 = time.time()
            search_result = qdrant_client.query_points(
                collection_name=collection,
                query=q_vector,
                limit=10,
            )
            latency_ms = (time.time() - t0) * 1000
            latencies.append(latency_ms)

            retrieved_ids = []
            for p in (search_result.points or []):
                pl = p.payload or {}
                retrieved_ids.append(pl.get("chunk_id", str(p.id)))

            truth_ids = [chunk_id]

            recalls_1.append(calculate_recall_at_k(retrieved_ids, truth_ids, 1))
            recalls_3.append(calculate_recall_at_k(retrieved_ids, truth_ids, 3))
            recalls_5.append(calculate_recall_at_k(retrieved_ids, truth_ids, 5))
            recalls_10.append(calculate_recall_at_k(retrieved_ids, truth_ids, 10))
            precisions_1.append(calculate_precision_at_k(retrieved_ids, truth_ids, 1))
            precisions_3.append(calculate_precision_at_k(retrieved_ids, truth_ids, 3))
            precisions_5.append(calculate_precision_at_k(retrieved_ids, truth_ids, 5))
            precisions_10.append(calculate_precision_at_k(retrieved_ids, truth_ids, 10))
            f1s_5.append(calculate_f1_score(precisions_5[-1], recalls_5[-1]))
            f1s_10.append(calculate_f1_score(precisions_10[-1], recalls_10[-1]))
            mrrs.append(calculate_mrr(retrieved_ids, truth_ids))
            hit_rates.append(calculate_hit_rate(retrieved_ids, truth_ids))
            ndcgs_3.append(calculate_ndcg_at_k(retrieved_ids, truth_ids, 3))
            ndcgs_5.append(calculate_ndcg_at_k(retrieved_ids, truth_ids, 5))
            ndcgs_10.append(calculate_ndcg_at_k(retrieved_ids, truth_ids, 10))
            aps.append(calculate_ap(retrieved_ids, truth_ids))

        n = max(len(recalls_1), 1)
        avg_latency = sum(latencies) / len(latencies) if latencies else 0

        return jsonify({
            "user_id": user_id,
            "collection": collection,
            "total_vectors": total_vectors,
            "queries_evaluated": n,
            "retrieval_metrics": {
                "recall": {
                    "at_1": round(sum(recalls_1) / n, 4),
                    "at_3": round(sum(recalls_3) / n, 4),
                    "at_5": round(sum(recalls_5) / n, 4),
                    "at_10": round(sum(recalls_10) / n, 4),
                },
                "precision": {
                    "at_1": round(sum(precisions_1) / n, 4),
                    "at_3": round(sum(precisions_3) / n, 4),
                    "at_5": round(sum(precisions_5) / n, 4),
                    "at_10": round(sum(precisions_10) / n, 4),
                },
                "f1_score": {
                    "at_5": round(sum(f1s_5) / n, 4),
                    "at_10": round(sum(f1s_10) / n, 4),
                },
                "mrr": round(sum(mrrs) / n, 4),
                "hit_rate": round(sum(hit_rates) / n, 4),
                "ndcg": {
                    "at_3": round(sum(ndcgs_3) / n, 4),
                    "at_5": round(sum(ndcgs_5) / n, 4),
                    "at_10": round(sum(ndcgs_10) / n, 4),
                },
                "map": round(sum(aps) / n, 4),
            },
            "latency": {
                "avg_ms": round(avg_latency, 2),
                "min_ms": round(min(latencies), 2) if latencies else 0,
                "max_ms": round(max(latencies), 2) if latencies else 0,
            },
            "embedding_config": {
                "model": "FastEmbed (bge-small-en-v1.5)",
                "dimension": EMBEDDING_DIMENSION,
                "batch_size": 32,
            },
            "search_config": {
                "top_k": 10,
                "similarity_threshold": DEFAULT_CONFIG.similarity_threshold,
                "distance_metric": DEFAULT_CONFIG.distance_metric,
            },
        })

    except Exception as e:
        logger.error(f"Metrics evaluation failed: {traceback.format_exc()}")
        return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
    init_services()
    print("\n" + "=" * 60)
    print("  MULTI-TENANT RAG SERVER (LOCAL MODE)")
    print("  No Docker needed!")
    print("  API: http://localhost:5000")
    print("  Each user gets isolated data in separate Qdrant collections")
    print("=" * 60 + "\n")
    app.run(host="0.0.0.0", port=5000, debug=False, threaded=True)
