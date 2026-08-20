"""Route-level coverage for the production no-answer retrieval policy.

The test uses an in-memory Qdrant double so it never consumes embedding API
quota or needs a pre-indexed local collection.
"""

import unittest
from types import SimpleNamespace
from unittest.mock import patch

import multi_tenant_server_local as server


class _BelowThresholdQdrant:
    def get_collection(self, collection_name):
        return SimpleNamespace(points_count=1)

    def query_points(self, **kwargs):
        point = SimpleNamespace(
            id="unrelated",
            score=0.10,
            payload={"content": "An unrelated but valid chunk of text.", "metadata": {}},
        )
        threshold = kwargs.get("score_threshold")
        return SimpleNamespace(points=[] if threshold and point.score < threshold else [point])


class _FakeQueryEmbedder:
    def __init__(self, *args, **kwargs):
        pass

    def embed_query(self, query):
        return [0.0] * server.EMBEDDING_DIMENSION


class OodRejectionIntegrationTest(unittest.TestCase):
    def test_chat_rejects_an_ood_query_without_sources(self):
        with patch.object(server, "qdrant_client", _BelowThresholdQdrant()), patch(
            "rag_system.retriever.query_embedder.QueryEmbedder", _FakeQueryEmbedder
        ):
            response = server.app.test_client().post(
                "/api/v1/chat",
                json={"user_id": 3, "message": "What is the capital of France?"},
            )

        self.assertEqual(response.status_code, 200)
        payload = response.json
        self.assertEqual(payload["chunks_used"], 0)
        self.assertEqual(payload["sources"], [])
        self.assertEqual(payload["confidence"], "low")


if __name__ == "__main__":
    unittest.main()
