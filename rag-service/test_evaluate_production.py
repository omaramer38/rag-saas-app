"""Fast unit coverage for threshold evaluation result reuse."""

import unittest
from unittest.mock import patch

import evaluate_production


class _Response:
    status_code = 200

    @staticmethod
    def json():
        return {
            "results": [
                {"chunk_id": "relevant", "dense_score": 0.45},
                {"chunk_id": "weak", "dense_score": 0.20},
            ]
        }


class SearchCacheTest(unittest.TestCase):
    def test_reuses_one_raw_search_and_filters_results_locally(self):
        with patch("evaluate_production.requests.post", return_value=_Response()) as post:
            baseline = evaluate_production.search("test query", threshold=0.0)
            filtered = evaluate_production.search("test query", threshold=0.30)

        self.assertEqual(post.call_count, 1)
        self.assertEqual([item["chunk_id"] for item in baseline], ["relevant", "weak"])
        self.assertEqual([item["chunk_id"] for item in filtered], ["relevant"])


if __name__ == "__main__":
    unittest.main()
