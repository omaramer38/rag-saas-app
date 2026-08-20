"""
=============================================================================
PRODUCTION-GRADE RAG EVALUATION
=============================================================================
Evaluates:
  1. In-domain retrieval quality (Recall, Precision, MRR, MAP, nDCG, Hit Rate)
  2. Out-of-domain rejection (False Retrieval Rate, Rejection Accuracy)
  3. Threshold analysis across multiple thresholds
  4. Combined overall accuracy

Uses the /api/v1/search endpoint which returns raw dense scores (no reranking)
for clean threshold-based evaluation.
=============================================================================
"""
import json
import math
import time
import requests
from collections import defaultdict

# --- Configuration ---
BASE_URL = "http://localhost:5000"
USER_ID = 3
DATASET_PATH = "gold_dataset_extended.json"
RESULTS_PATH = "evaluation_results.json"

THRESHOLDS = [0.20, 0.25, 0.30, 0.35, 0.40, 0.45, 0.50, 0.55, 0.60]
TOP_K = 20

# Every threshold is evaluated against the same raw dense ranking.  Keeping
# that ranking in memory avoids repeating embedding and vector-search work for
# each threshold, while still applying the exact requested score cutoff.
SEARCH_CACHE = {}


# --- Metric Functions ---
def precision_at_k(retrieved_ids, gold_map, k):
    relevant = sum(1 for cid in retrieved_ids[:k] if gold_map.get(cid, 0) > 0)
    return relevant / k if k > 0 else 0.0


def recall_at_k(retrieved_ids, gold_map, k):
    total_relevant = sum(1 for v in gold_map.values() if v > 0)
    if total_relevant == 0:
        return 1.0
    found = sum(1 for cid in retrieved_ids[:k] if gold_map.get(cid, 0) > 0)
    return found / total_relevant


def f1_score(precision, recall):
    if precision + recall == 0:
        return 0.0
    return 2 * precision * recall / (precision + recall)


def mrr(retrieved_ids, gold_map):
    for i, cid in enumerate(retrieved_ids):
        if gold_map.get(cid, 0) > 0:
            return 1.0 / (i + 1)
    return 0.0


def average_precision(retrieved_ids, gold_map):
    total_relevant = sum(1 for v in gold_map.values() if v > 0)
    if total_relevant == 0:
        return 0.0
    hits = 0
    sum_precision = 0.0
    for i, cid in enumerate(retrieved_ids):
        if gold_map.get(cid, 0) > 0:
            hits += 1
            sum_precision += hits / (i + 1)
    return sum_precision / total_relevant


def ndcg_at_k(retrieved_ids, gold_map, k):
    dcg = 0.0
    for i, cid in enumerate(retrieved_ids[:k]):
        rel = gold_map.get(cid, 0)
        dcg += (2 ** rel - 1) / math.log2(i + 2)
    ideal_rels = sorted(gold_map.values(), reverse=True)[:k]
    idcg = 0.0
    for i, rel in enumerate(ideal_rels):
        idcg += (2 ** rel - 1) / math.log2(i + 2)
    return dcg / idcg if idcg > 0 else 0.0


def hit_rate(retrieved_ids, gold_map):
    return 1.0 if any(gold_map.get(cid, 0) > 0 for cid in retrieved_ids) else 0.0


# --- Search Function ---
def search(query, threshold=0.0):
    if query not in SEARCH_CACHE:
        try:
            r = requests.post(
                f"{BASE_URL}/api/v1/search",
                json={"user_id": USER_ID, "query": query, "top_k": TOP_K, "threshold": 0.0},
                timeout=30,
            )
            if r.status_code != 200:
                print(f"  [!] Search failed for '{query[:50]}': HTTP {r.status_code}")
                return []
            SEARCH_CACHE[query] = r.json().get("results", [])
        except requests.exceptions.ConnectionError:
            print(f"  [!] Connection error -- is the RAG server running on {BASE_URL}?")
            return []
        except Exception as e:
            print(f"  [!] Search error: {e}")
            return []

    # The endpoint returns the raw ranking once.  Score filtering here makes
    # threshold analysis deterministic and avoids re-embedding the query.
    return [
        result for result in SEARCH_CACHE[query]
        if result.get("dense_score", 0.0) >= threshold
    ]


# --- Load Dataset ---
def load_dataset():
    with open(DATASET_PATH) as f:
        dataset = json.load(f)
    return dataset


# --- In-Domain Evaluation ---
def evaluate_in_domain(queries, threshold=0.0):
    metrics = {
        "precision": {1: [], 3: [], 5: [], 10: []},
        "recall": {1: [], 3: [], 5: [], 10: []},
        "mrr": [],
        "map": [],
        "ndcg5": [],
        "hit_rate": [],
    }
    details = []

    for q_entry in queries:
        query = q_entry["query"]
        gold_map = {}
        for chunk_id, info in q_entry.get("relevant_chunks", {}).items():
            gold_map[chunk_id] = info["relevance"]

        results = search(query, threshold=threshold)
        retrieved_ids = [r["chunk_id"] for r in results]
        best_score = results[0]["dense_score"] if results else 0.0

        for k in [1, 3, 5, 10]:
            metrics["precision"][k].append(precision_at_k(retrieved_ids, gold_map, k))
            metrics["recall"][k].append(recall_at_k(retrieved_ids, gold_map, k))
        metrics["mrr"].append(mrr(retrieved_ids, gold_map))
        metrics["map"].append(average_precision(retrieved_ids, gold_map))
        metrics["ndcg5"].append(ndcg_at_k(retrieved_ids, gold_map, 5))
        metrics["hit_rate"].append(hit_rate(retrieved_ids, gold_map))

        details.append({
            "query": query,
            "type": "in_domain",
            "retrieved_count": len(results),
            "best_score": best_score,
            "mrr": metrics["mrr"][-1],
            "hit": metrics["hit_rate"][-1],
        })

    n = max(len(queries), 1)
    aggregate = {}
    for k in [1, 3, 5, 10]:
        aggregate[f"precision@{k}"] = round(sum(metrics["precision"][k]) / n, 4)
        aggregate[f"recall@{k}"] = round(sum(metrics["recall"][k]) / n, 4)
    aggregate["mrr"] = round(sum(metrics["mrr"]) / n, 4)
    aggregate["map"] = round(sum(metrics["map"]) / n, 4)
    aggregate["ndcg@5"] = round(sum(metrics["ndcg5"]) / n, 4)
    aggregate["hit_rate"] = round(sum(metrics["hit_rate"]) / n, 4)

    return aggregate, details


# --- Out-of-Domain Evaluation ---
def evaluate_out_of_domain(queries, threshold=0.0):
    total = len(queries)
    if total == 0:
        return {"ood_rejection_accuracy": 1.0, "false_retrieval_rate": 0.0, "no_answer_accuracy": 1.0}, []

    rejected = 0
    false_retrieved = 0
    details = []

    for q_entry in queries:
        query = q_entry["query"]
        results = search(query, threshold=threshold)
        retrieved_count = len(results)
        best_score = results[0]["dense_score"] if results else 0.0

        is_rejected = (retrieved_count == 0)
        if is_rejected:
            rejected += 1
        else:
            false_retrieved += 1

        details.append({
            "query": query,
            "type": "out_of_domain",
            "category": q_entry.get("category", ""),
            "retrieved_count": retrieved_count,
            "best_score": best_score,
            "rejected": is_rejected,
        })

    rejection_accuracy = rejected / total
    false_retrieval_rate = false_retrieved / total

    metrics = {
        "ood_rejection_accuracy": round(rejection_accuracy, 4),
        "false_retrieval_rate": round(false_retrieval_rate, 4),
        "no_answer_accuracy": round(rejection_accuracy, 4),
    }
    return metrics, details


# --- Threshold Analysis ---
def threshold_analysis(in_domain_queries, ood_queries, thresholds):
    results = []

    for t in thresholds:
        print(f"\n  Evaluating threshold = {t:.2f} ...")

        id_metrics, _ = evaluate_in_domain(in_domain_queries, threshold=t)
        ood_metrics, _ = evaluate_out_of_domain(ood_queries, threshold=t)

        n_id = len(in_domain_queries)
        n_ood = len(ood_queries)
        total = n_id + n_ood
        overall_accuracy = (
            id_metrics["hit_rate"] * n_id + ood_metrics["ood_rejection_accuracy"] * n_ood
        ) / total if total > 0 else 0

        entry = {
            "threshold": t,
            "recall@5": id_metrics["recall@5"],
            "precision@5": id_metrics["precision@5"],
            "mrr": id_metrics["mrr"],
            "hit_rate": id_metrics["hit_rate"],
            "ood_rejection_accuracy": ood_metrics["ood_rejection_accuracy"],
            "false_retrieval_rate": ood_metrics["false_retrieval_rate"],
            "overall_accuracy": round(overall_accuracy, 4),
        }
        results.append(entry)

    return results


def find_optimal_threshold(threshold_results):
    best_score = -1
    best = None
    for entry in threshold_results:
        score = (
            0.40 * entry["overall_accuracy"]
            + 0.30 * entry["recall@5"]
            + 0.30 * (1.0 - entry["false_retrieval_rate"])
        )
        entry["composite_score"] = round(score, 4)
        if score > best_score:
            best_score = score
            best = entry
    return best


# --- Print Helpers (ASCII only, no Unicode) ---
def print_header(text):
    print(f"\n{'=' * 70}")
    print(f"  {text}")
    print(f"{'=' * 70}")


def print_subheader(text):
    print(f"\n{'-' * 70}")
    print(f"  {text}")
    print(f"{'-' * 70}")


def print_metrics_table(metrics):
    print(f"\n  {'Metric':<25} {'Value':>10}")
    print(f"  {'-' * 35}")
    for k in [1, 3, 5, 10]:
        print(f"  {'Precision@' + str(k):<25} {metrics[f'precision@{k}'] * 100:>9.1f}%")
        print(f"  {'Recall@' + str(k):<25} {metrics[f'recall@{k}'] * 100:>9.1f}%")
    print(f"  {'-' * 35}")
    print(f"  {'MRR':<25} {metrics['mrr'] * 100:>9.1f}%")
    print(f"  {'MAP':<25} {metrics['map'] * 100:>9.1f}%")
    print(f"  {'nDCG@5':<25} {metrics['ndcg@5'] * 100:>9.1f}%")
    print(f"  {'Hit Rate':<25} {metrics['hit_rate'] * 100:>9.1f}%")


def print_ood_metrics(metrics):
    print(f"\n  {'Metric':<35} {'Value':>10}")
    print(f"  {'-' * 45}")
    print(f"  {'OOD Rejection Accuracy':<35} {metrics['ood_rejection_accuracy'] * 100:>9.1f}%")
    print(f"  {'False Retrieval Rate':<35} {metrics['false_retrieval_rate'] * 100:>9.1f}%")
    print(f"  {'No-Answer Accuracy':<35} {metrics['no_answer_accuracy'] * 100:>9.1f}%")


def print_threshold_table(threshold_results):
    print(f"\n  {'Threshold':>10} | {'Recall@5':>9} | {'Prec@5':>7} | {'MRR':>7} | {'OOD Reject':>10} | {'FalseRet':>9} | {'Overall':>8}")
    print(f"  {'-' * 10}-+-{'-' * 9}-+-{'-' * 7}-+-{'-' * 7}-+-{'-' * 10}-+-{'-' * 9}-+-{'-' * 8}")
    for entry in threshold_results:
        t = entry["threshold"]
        r5 = entry["recall@5"] * 100
        p5 = entry["precision@5"] * 100
        mrr_val = entry["mrr"] * 100
        ood = entry["ood_rejection_accuracy"] * 100
        fr = entry["false_retrieval_rate"] * 100
        oa = entry["overall_accuracy"] * 100
        print(f"  {t:>10.2f} | {r5:>8.1f}% | {p5:>6.1f}% | {mrr_val:>6.1f}% | {ood:>9.1f}% | {fr:>8.1f}% | {oa:>7.1f}%")


def print_ood_details(details):
    print(f"\n  {'#':>3}  {'Query':<45} {'Retrieved':>9}  {'Best':>6}  {'Status':>10}")
    print(f"  {'---':>3}  {'-' * 45} {'-' * 9}  {'-' * 6}  {'-' * 10}")
    for i, d in enumerate(details, 1):
        q = d["query"][:44]
        n = d["retrieved_count"]
        s = d["best_score"]
        status = "[REJECT]" if d["rejected"] else "[FALSE]"
        print(f"  {i:>3}  {q:<45} {n:>9}  {s:>6.3f}  {status:>10}")


# --- Main ---
def main():
    print_header("PRODUCTION-GRADE RAG EVALUATION")
    print(f"  Server:    {BASE_URL}")
    print(f"  User ID:   {USER_ID}")
    print(f"  Dataset:   {DATASET_PATH}")
    print(f"  Top-K:     {TOP_K}")

    dataset = load_dataset()
    in_domain = dataset.get("in_domain_queries", [])
    ood = dataset.get("out_of_domain_queries", [])

    print(f"  In-domain queries: {len(in_domain)}")
    print(f"  OOD queries:       {len(ood)}")

    # === Part 1: Current baseline ===
    print_header("PART 1: CURRENT BASELINE (threshold = 0.0, no filtering)")

    print_subheader("In-Domain Retrieval Quality")
    id_metrics, id_details = evaluate_in_domain(in_domain, threshold=0.0)
    print_metrics_table(id_metrics)
    for d in id_details:
        print(f"  > {d['query'][:50]:<52} MRR={d['mrr']:.3f}  Hit={d['hit']:.0f}  Best={d['best_score']:.3f}")

    print_subheader("Out-of-Domain Rejection")
    ood_metrics, ood_details = evaluate_out_of_domain(ood, threshold=0.0)
    print_ood_metrics(ood_metrics)
    print_ood_details(ood_details)

    # === Part 2: Threshold Analysis ===
    print_header("PART 2: THRESHOLD ANALYSIS")
    print(f"\n  Testing thresholds: {THRESHOLDS}\n")

    threshold_results = threshold_analysis(in_domain, ood, THRESHOLDS)
    print_threshold_table(threshold_results)

    # === Part 3: Optimal Threshold ===
    print_header("PART 3: OPTIMAL THRESHOLD RECOMMENDATION")
    optimal = find_optimal_threshold(threshold_results)
    if optimal:
        print(f"\n  [OK] Recommended threshold: {optimal['threshold']:.2f}")
        print(f"       Composite score:       {optimal['composite_score']:.4f}")
        print(f"       In-domain Recall@5:    {optimal['recall@5'] * 100:.1f}%")
        print(f"       In-domain Precision@5: {optimal['precision@5'] * 100:.1f}%")
        print(f"       MRR:                   {optimal['mrr'] * 100:.1f}%")
        print(f"       OOD Rejection:         {optimal['ood_rejection_accuracy'] * 100:.1f}%")
        print(f"       False Retrieval Rate:  {optimal['false_retrieval_rate'] * 100:.1f}%")
        print(f"       Overall Accuracy:      {optimal['overall_accuracy'] * 100:.1f}%")
        print(f"\n  Rationale:")
        print(f"    This threshold maximizes the trade-off between retrieving relevant")
        print(f"    in-domain answers and correctly rejecting irrelevant OOD queries.")
        print(f"    Composite = 0.4*Overall + 0.3*Recall@5 + 0.3*(1 - FalseRetrieval)")

    # === Part 4: Re-evaluate with optimal threshold ===
    if optimal and optimal["threshold"] > 0.0:
        print_header(f"PART 4: RE-EVALUATION WITH OPTIMAL THRESHOLD ({optimal['threshold']:.2f})")

        print_subheader("In-Domain (with threshold filtering)")
        id_opt, id_opt_details = evaluate_in_domain(in_domain, threshold=optimal["threshold"])
        print_metrics_table(id_opt)
        for d in id_opt_details:
            print(f"  > {d['query'][:50]:<52} MRR={d['mrr']:.3f}  Hit={d['hit']:.0f}  Best={d['best_score']:.3f}")

        print_subheader("OOD (with threshold filtering)")
        ood_opt, ood_opt_details = evaluate_out_of_domain(ood, threshold=optimal["threshold"])
        print_ood_metrics(ood_opt)
        print_ood_details(ood_opt_details)

    # === Save Results ===
    output = {
        "metadata": {
            "server": BASE_URL,
            "user_id": USER_ID,
            "in_domain_count": len(in_domain),
            "ood_count": len(ood),
            "top_k": TOP_K,
            "evaluation_time": time.strftime("%Y-%m-%d %H:%M:%S"),
        },
        "baseline": {
            "threshold": 0.0,
            "in_domain_metrics": id_metrics,
            "ood_metrics": ood_metrics,
        },
        "threshold_analysis": threshold_results,
        "optimal_threshold": optimal,
    }
    with open(RESULTS_PATH, "w") as f:
        json.dump(output, f, indent=2, ensure_ascii=False)

    print_header("DONE")
    print(f"  Results saved to: {RESULTS_PATH}")
    print(f"  Total queries evaluated: {len(in_domain) + len(ood)}")


if __name__ == "__main__":
    main()
