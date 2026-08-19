# Multi-Tenant RAG Architecture

## Problem
Current RAG system uses ONE collection for ALL users.
We need: Each doctor's data ISOLATED from others.

## Solution: Collection-per-User (NO changes to core RAG code)

### How it works:

```
Qdrant Vector DB
├── Collection: user_1_dental     ← Dr. Ahmed (Dentistry)
│   ├── chunk_1 (dental topic)
│   ├── chunk_2 (dental topic)
│   └── chunk_3 (dental topic)
│
├── Collection: user_2_internal   ← Dr. Mohamed (Internal Medicine)
│   ├── chunk_1 (internal topic)
│   ├── chunk_2 (internal topic)
│   └── chunk_3 (internal topic)
│
└── Collection: user_3_derma      ← Dr. Khalid (Dermatology)
    ├── chunk_1 (derma topic)
    ├── chunk_2 (derma topic)
    └── chunk_3 (derma topic)
```

### Key Insight:
The `QdrantVectorStore` reads collection name from `RetrieverConfig.qdrant_collection`.
We can create DIFFERENT configs for DIFFERENT users = isolated collections.

### API Endpoints (New wrapper server):

```
POST /api/v1/documents/upload
  Body: { file: PDF, user_id: int }
  → Ingests PDF into user-specific collection

POST /api/v1/chat
  Body: { user_id: int, session_id: str, message: str }
  → Searches ONLY in user's collection

DELETE /api/v1/documents/{document_id}
  → Deletes from user's collection only

GET /api/v1/health
  → System health check
```

### What we DON'T change:
- ❌ pipeline.py (MedicalRetriever)
- ❌ vector_store.py (QdrantVectorStore)
- ❌ search.py (VectorSearchEngine)
- ❌ reranker.py
- ❌ generator.py (MedicalGenerator)
- ❌ prompt_builder.py
- ❌ Any fine-tuning code

### What we ADD:
- ✅ New Flask app (multi_tenant_server.py) - wrapper
- ✅ User-specific collection management
- ✅ File upload endpoint
- ✅ User isolation in search
- ✅ Docker integration

### Flow:

1. Doctor uploads PDF via Laravel → sent to RAG API
2. RAG API ingests PDF into user-specific Qdrant collection
3. Doctor sends chat message → Laravel sends to RAG API
4. RAG API searches ONLY in that user's collection
5. Response returned to doctor
