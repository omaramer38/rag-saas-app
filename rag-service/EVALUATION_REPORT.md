# Production Retrieval Evaluation Report

Generated on 2026-08-20 against user `3`'s indexed WHO diabetes guideline collection.

## Benchmark composition

- In-domain queries: 10
- Out-of-domain (OOD) queries: 20
- OOD categories include geography, history, programming, physics, cooking, sports, finance, politics, and diseases not covered by the document.
- Every OOD entry has `gold_answer: NOT_FOUND` and no gold chunks.

## Baseline: no retrieval threshold

| Metric | Result |
|---|---:|
| Precision@5 | 22.0% |
| Recall@5 | 41.7% |
| MRR | 70.9% |
| MAP | 45.1% |
| nDCG@5 | 45.9% |
| Hit Rate | 80.0% |
| OOD Rejection Accuracy | 0.0% |
| False Retrieval Rate | 100.0% |
| No-Answer Accuracy | 0.0% |

Without a cutoff, vector similarity always yields nearest chunks. That produces an unrelated answer context for every OOD question.

## Threshold comparison

| Threshold | Recall@5 | Precision@5 | OOD rejection | False retrieval | Overall accuracy |
|---:|---:|---:|---:|---:|---:|
| 0.20 | 41.7% | 22.0% | 75.0% | 25.0% | 73.3% |
| 0.25 | 41.7% | 22.0% | 100.0% | 0.0% | 90.0% |
| 0.30 | 41.7% | 22.0% | 100.0% | 0.0% | 90.0% |
| 0.35 | 31.7% | 18.0% | 100.0% | 0.0% | 86.7% |
| 0.40 | 25.0% | 14.0% | 100.0% | 0.0% | 83.3% |
| 0.45 | 8.3% | 4.0% | 100.0% | 0.0% | 73.3% |
| 0.50 | 5.0% | 2.0% | 100.0% | 0.0% | 70.0% |
| 0.55 | 5.0% | 2.0% | 100.0% | 0.0% | 70.0% |
| 0.60 | 0.0% | 0.0% | 100.0% | 0.0% | 66.7% |

## Recommendation

Use a dense-score threshold of **0.25**. It is the least restrictive evaluated threshold that rejects every benchmark OOD query while retaining the maximum observed Recall@5 (41.7%). It achieved:

- OOD Rejection Accuracy: **100.0%**
- False Retrieval Rate: **0.0%**
- In-domain Hit Rate: **70.0%**
- Overall Accuracy: **90.0%**

`0.30` ties on the aggregate benchmark, but `0.25` is preferable as the lower cutoff: it leaves more margin for valid but lower-similarity in-domain questions.

## Metric definitions

- **OOD Rejection Accuracy / No-Answer Accuracy:** OOD queries whose retrieved result list is empty.
- **False Retrieval Rate:** OOD queries that return one or more chunks.
- **In-domain Accuracy:** in-domain Hit Rate: at least one gold-relevant chunk is retrieved.
- **Overall Accuracy:** weighted success rate over all benchmark queries: in-domain hit or correct OOD rejection.

## Reproducibility

1. Start the local RAG server: `python multi_tenant_server_local.py`.
2. Ensure the benchmark user's document has been indexed.
3. Run `python evaluate_production.py`.

The machine-readable output is saved to `evaluation_results.json`. Add OOD cases by appending objects to `out_of_domain_queries` in `gold_dataset_extended.json`.
