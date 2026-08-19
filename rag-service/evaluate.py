"""
Proper evaluation with gold relevance labels.
Compares: Current Ranking (dense + chapter boost) vs Reranker (Cohere Rerank)
"""
import json
import requests
import math
import time

BASE = "http://localhost:5000"
USER_ID = 3

# Load gold dataset
with open("gold_dataset.json") as f:
    gold = json.load(f)

def get_retrieved_chunks(query, top_k=10):
    """Get retrieved chunks from the RAG server."""
    r = requests.post(f"{BASE}/api/v1/chat",
        json={"user_id": USER_ID, "message": query, "top_k": top_k},
        timeout=30)
    d = r.json()
    return d.get("sources", [])

def get_chunk_ids_from_gold(gold_entry):
    """Extract chunk_ids and relevance scores from gold dataset.
    Gold format: {"chunk_id": {"relevance": 0|1|2, "reason": "..."}}
    """
    relevant = gold_entry.get("relevant_chunks", {})
    gold_map = {}  # chunk_id -> relevance
    for chunk_id, info in relevant.items():
        gold_map[chunk_id] = info["relevance"]
    return gold_map

def precision_at_k(retrieved_ids, gold_map, k):
    """Precision@K: fraction of top-K that are relevant."""
    relevant = sum(1 for cid in retrieved_ids[:k] if gold_map.get(cid, 0) > 0)
    return relevant / k if k > 0 else 0

def recall_at_k(retrieved_ids, gold_map, k):
    """Recall@K: fraction of relevant docs found in top-K."""
    total_relevant = sum(1 for v in gold_map.values() if v > 0)
    if total_relevant == 0:
        return 1.0
    found = sum(1 for cid in retrieved_ids[:k] if gold_map.get(cid, 0) > 0)
    return found / total_relevant

def mrr(retrieved_ids, gold_map):
    """Mean Reciprocal Rank: 1/rank of first relevant result."""
    for i, cid in enumerate(retrieved_ids):
        if gold_map.get(cid, 0) > 0:
            return 1.0 / (i + 1)
    return 0.0

def map_score(retrieved_ids, gold_map):
    """Mean Average Precision."""
    total_relevant = sum(1 for v in gold_map.values() if v > 0)
    if total_relevant == 0:
        return 1.0
    
    hits = 0
    sum_precision = 0.0
    for i, cid in enumerate(retrieved_ids):
        if gold_map.get(cid, 0) > 0:
            hits += 1
            sum_precision += hits / (i + 1)
    
    return sum_precision / total_relevant

def ndcg_at_k(retrieved_ids, gold_map, k):
    """NDCG@K: normalized discounted cumulative gain."""
    # DCG
    dcg = 0.0
    for i, cid in enumerate(retrieved_ids[:k]):
        rel = gold_map.get(cid, 0)
        dcg += (2**rel - 1) / math.log2(i + 2)
    
    # Ideal DCG
    ideal_rels = sorted(gold_map.values(), reverse=True)[:k]
    idcg = 0.0
    for i, rel in enumerate(ideal_rels):
        idcg += (2**rel - 1) / math.log2(i + 2)
    
    return dcg / idcg if idcg > 0 else 0.0

def hit_rate(retrieved_ids, gold_map):
    """Hit Rate: 1 if any relevant doc in retrieved set, 0 otherwise."""
    return 1.0 if any(gold_map.get(cid, 0) > 0 for cid in retrieved_ids) else 0.0

# ═══════════════════════════════════════════════════════════════════
# EVALUATE CURRENT RANKING
# ═══════════════════════════════════════════════════════════════════
print("=" * 70)
print("  EVALUATION: Gold Relevance Labels")
print("=" * 70)

all_metrics = {
    "precision": {1: [], 3: [], 5: [], 10: []},
    "recall": {1: [], 3: [], 5: [], 10: []},
    "mrr": [],
    "map": [],
    "ndcg5": [],
    "hit_rate": [],
}

for q_entry in gold["queries"]:
    query = q_entry["query"]
    gold_map = get_chunk_ids_from_gold(q_entry)
    
    retrieved = get_retrieved_chunks(query, top_k=10)
    retrieved_ids = [s.get("chunk_id", "") for s in retrieved]
    
    # Calculate metrics
    for k in [1, 3, 5, 10]:
        all_metrics["precision"][k].append(precision_at_k(retrieved_ids, gold_map, k))
        all_metrics["recall"][k].append(recall_at_k(retrieved_ids, gold_map, k))
    
    all_metrics["mrr"].append(mrr(retrieved_ids, gold_map))
    all_metrics["map"].append(map_score(retrieved_ids, gold_map))
    all_metrics["ndcg5"].append(ndcg_at_k(retrieved_ids, gold_map, 5))
    all_metrics["hit_rate"].append(hit_rate(retrieved_ids, gold_map))
    
    # Print detailed ranking
    print(f"\n--- {query} ---")
    print(f"  Gold relevant chunks: {len([v for v in gold_map.values() if v > 0])}")
    for i, s in enumerate(retrieved[:5]):
        cid = s.get("chunk_id", "")
        rel = gold_map.get(cid, 0)
        ch = s.get("chapter", "?")[:35]
        sec = s.get("section", "")[:25]
        score = s.get("score", 0)
        rel_label = {0: "IRRELEVANT", 1: "SUPPORTING", 2: "DIRECT ANSWER"}[rel]
        print(f"  Rank {i+1}: Score={score:.3f} | {ch} > {sec} | Relevance={rel} ({rel_label})")

# Print aggregate metrics
print("\n" + "=" * 70)
print("  AGGREGATE METRICS (Current Ranking)")
print("=" * 70)
n = len(gold["queries"])
for k in [1, 3, 5, 10]:
    p = sum(all_metrics["precision"][k]) / n * 100
    r = sum(all_metrics["recall"][k]) / n * 100
    print(f"P@{k}: {p:.1f}%  R@{k}: {r:.1f}%")
mrr_val = sum(all_metrics["mrr"]) / n * 100
map_val = sum(all_metrics["map"]) / n * 100
ndcg_val = sum(all_metrics["ndcg5"]) / n * 100
hr_val = sum(all_metrics["hit_rate"]) / n * 100
print(f"MRR: {mrr_val:.1f}%  MAP: {map_val:.1f}%  nDCG@5: {ndcg_val:.1f}%  Hit Rate: {hr_val:.1f}%")
