# WOLO Chatbot: GCP Cost Estimation & Budgeting

This document outlines the projected monthly cost of running the WOLO Real Estate Chatbot on Google Cloud Platform (GCP). It covers two environments: **Developer / Staging** (minimized cost leveraging free tiers) and **Production Scale** (designed for medium traffic, high availability, and performance).

---

## 1. Cost Summary Table

| GCP Component | Dev/Staging Configuration | Dev/Staging Cost | Production Configuration | Production Cost |
| :--- | :--- | :---: | :--- | :---: |
| **GCP Cloud Run** | 3 Services, min instance 0 | `$0.00` *(Free Tier)* | 3 Services, min instance 1, auto-scale | `$45.00` |
| **Vertex AI (Gemini 1.5 Flash)** | ~10k queries/month | `$1.50` | ~100k queries/month | `$15.00` |
| **Cloud SQL (PostgreSQL)** | Shared CPU, `db-f1-micro` (10 GB SSD) | `$11.07` | Custom `db-custom-1-3840` (50 GB SSD) | `$58.50` |
| **Memorystore for Redis** | Local Docker / basic mock | `$0.00` | Basic Tier (M1 - 1 GB) | `$35.00` |
| **Cloud Firestore** | <50k reads/writes/day | `$0.00` *(Free Tier)* | ~500k reads/writes/day | `$1.20` |
| **Cloud Pub/Sub** | <10 GB throughput/month | `$0.00` *(Free Tier)* | ~100 GB throughput/month | `$3.60` |
| **GCP API Gateway** | <2 million calls/month | `$0.00` *(Free Tier)* | ~5 million calls/month | `$9.00` |
| **Networking & Egress** | Minimal traffic | `$0.00` | Egress, Load Balancer | `$15.00` |
| **Total Estimated / Month** | | **`$12.57`** | | **`$182.30`** |

---

## 2. Component-by-Component Cost Breakdown

### A. GCP Cloud Run
*   **Dev/Staging**: Services scale down to `0` instances when idle. Daily usage fits well within the GCP free tier (2 million requests/month, 180k vCPU-seconds, 360k GiB-seconds).
*   **Production**: Set minimum instances to `1` on the `chat-orchestrator` to avoid cold-start delays. vCPUs allocated: 1 vCPU, 2 GB RAM per instance.
    *   *Calculation*: $0.00002400/vCPU-sec * 3600 sec * 24 hours * 30 days = ~$62 per vCPU-month. With idle discounts and active scaling, 3 services average ~$45/month.

### B. GCP Vertex AI (Gemini 1.5 Flash & Embeddings)
*   **Intent Matching & Chat**: Powered by Gemini 1.5 Flash.
    *   *Prompt Input Cost*: $0.075 per 1,000,000 tokens.
    *   *Response Output Cost*: $0.30 per 1,000,000 tokens.
    *   *Average Session (10 turns)*: ~15,000 input tokens, ~1,500 output tokens. Cost per session = ~$0.001575.
*   **Property Embeddings**: Generated once upon property upload using text-embedding model (`$0.025` per 1,000,000 tokens). Minimal cost.
*   **Total Cost**:
    *   *Dev/Staging (1,000 sessions/month)*: ~$1.50
    *   *Production (10,000 sessions/month)*: ~$15.00

### C. Cloud SQL (PostgreSQL with `pgvector`)
*   **Dev/Staging**: `db-f1-micro` shared CPU instance ($9.37/month) + 10 GB SSD Storage ($1.70/month) = **`$11.07/month`**.
*   **Production**: Dedicated `db-custom-1-3840` (1 vCPU, 3.75 GB RAM) instance ($50.00/month) + 50 GB SSD Storage with auto-increase ($8.50/month) = **`$58.50/month`**.

### D. Memorystore for Redis
> [!NOTE]
> For Dev/Staging, session caching can be run via SQLite cache fallback or in-memory array to eliminate Redis service cost completely.
*   **Production**: A small Basic Tier (1 GB) Redis instance is required to handle high-concurrency session caching ($0.049/hour * 730 hours = **`$35.77/month`**).

### E. Cloud Firestore
*   **Dev/Staging**: Fits completely within the free tier (1 GB storage, 50,000 reads/day, 20,000 writes/day).
*   **Production**:
    *   5 GB storage: ~$0.90
    *   500,000 reads/month: ~$0.30
    *   **Total**: **`$1.20/month`**.

### F. Cloud Pub/Sub & API Gateway
*   **Pub/Sub**: Free for the first 10 GB of data. Production at 100 GB volume costs $0.04/GB beyond the free tier = **`$3.60/month`**.
*   **API Gateway**: Free for the first 2 million calls. Production at 5 million calls costs $3.00 per million calls beyond the free tier = **`$9.00/month`**.

---

## 3. Cost Optimization Strategies

> [!TIP]
> **To maintain lowest possible costs during development, follow these guidelines:**
> 1. **Aggressive Cache Control**: Cache text embeddings in a local repository or SQLite database for local developer tests to avoid redundant Vertex AI API calls.
> 2. **Instance Limits**: Ensure Cloud Run max instances are capped at `2` or `3` in developer projects to prevent runaway costs from testing loops or load trials.
> 3. **Automatic Shutdowns**: If using compute engines, configure cron schedules to delete/shut down staging environments during non-working hours.
> 4. **Mocking External APIs**: Use the established `MockHttpClient` in tests so unit and integration test runs generate `$0` Vertex AI API charges.
