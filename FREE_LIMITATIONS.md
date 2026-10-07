# Free-Tier Environment Limitations & Guardrails

This document outlines the capabilities and intentional limitations of **LinkPilot SEO** when operating on zero-budget infrastructure (Render Free Web Service + Supabase Free PostgreSQL).

---

## ✅ Fully Functional Features ($0 Cost)

The following core modules and features run with **100% parity** compared to paid enterprise hosting:

| Feature | Status | Notes |
|---|---|---|
| **User Authentication & Role Permissions** | Fully Functional | Admin & SEO Specialist RBAC, Sanctum API Keys |
| **Project & Domain Ownership** | Fully Functional | Multi-project segmentation, DNS & File verification |
| **SSRF Security Protection** | Fully Functional | Revalidates all IP addresses, blocks 127.0.0.1, 10.x, 169.254.x, 192.168.x |
| **Separated Status Architecture** | Fully Functional | Independent tracking of `DISCOVERED`, `CRAWLED`, `INDEXED`, and `LOST` |
| **Health Check & URL Parsing** | Fully Functional | HTTP status, meta robots, x-robots-tag, canonical, anchor text, rel attributes |
| **Search Engine Authorization Vault** | Fully Functional | Credentials stored with AES-256 (`encrypted:array` cast) |
| **Search Engine Integrations** | Fully Functional | Bing Webmaster, IndexNow, and GSC URL Inspection (strict ownership verification) |
| **Google Indexing API Strict Gating** | Fully Functional | Hard-enforced restriction to `JobPosting` and `BroadcastEvent` |
| **Exponential Backoff Retry Engine** | Fully Functional | Intelligently retries transient errors with exponential delay; skips 404/410 |
| **Audit Logs & API Request Telemetry** | Fully Functional | Logs external HTTP calls with sanitized payloads and latency metrics |
| **Third-Party SEO Metrics** | Fully Functional | Moz DA/PA, Ahrefs DR, SEMrush AS metrics with fallback calibration algorithms |
| **Slack & Email Drop Alerts** | Fully Functional | Dispatches formatted card alerts immediately when backlink drops or is lost |
| **Branded Executive PDF Export** | Fully Functional | Generates client-ready audit reports directly in-memory via DomPDF |
| **System Health Probe (`/health`)** | Fully Functional | Validates app, database latency, queue readiness, and version |

---

## ⚠️ Intentionally Limited Features (Free-Tier Guardrails)

To operate reliably on a **$0 budget** without exceeding resource quotas or incurring surprise charges, the following parameters are intentionally restricted:

### 1. Render Free Web Service Spin-Down (Cold Start)
- **Behavior**: Render free web services automatically spin down into a sleep state after **15 minutes of inactivity**.
- **Impact**: The first request sent after idle takes **30 to 50 seconds** while the Docker container wakes up. Subsequent requests respond instantly (<150ms).
- **Tip**: You can use a free uptime monitor (such as UptimeRobot or Cron-job.org) pinging `https://your-app.onrender.com/health` every 10 minutes to prevent container sleep during working hours.

### 2. RAM Constraint (512 MB Free Tier Limit)
- **Behavior**: Render free containers are capped at 512 MB total RAM.
- **Guardrails Implemented**:
  - `MAX_RESPONSE_SIZE = 2MB (2097152 bytes)`: Prevents downloading massive multi-megabyte pages into memory during crawler checks.
  - `memory_limit = 256M` configured in `docker/php.ini`.
  - Alpine Linux base image with lightweight Nginx + PHP-FPM + Supervisor (~35MB idle footprint).

### 3. Bulk Import Limits
- **Parameter**: `MAX_URLS_PER_IMPORT = 50` (Configurable in `.env`).
- **Reasoning**: Processing thousands of URLs in a single web request would trigger Render's HTTP request timeout (100 seconds).
- **Recommendation**: For large datasets on the free tier, import in batches of 50 URLs or use the API endpoint with spaced requests.

### 4. Background Queue Worker Model
- **Behavior**: On enterprise setups, queue workers run as separate background instances. On the free tier, a lightweight supervisor process (`php artisan queue:work database`) runs **inside the web container**.
- **Queue Connection**: Uses PostgreSQL `jobs` table (`QUEUE_CONNECTION=database`). No paid Redis instance is required.
- **Worker Concurrency**: Single worker process with `--memory=128` to maintain container stability.

### 5. Crawler Frequency & Concurrency
- **Parameter**: `MAX_URL_CHECKS_PER_RUN = 25`.
- **Parameter**: `MAX_CONCURRENT_CHECKS = 3`.
- **Reasoning**: Prevents burst traffic that could cause target hosts to rate-limit the Render shared egress IP.

### 6. Search Engine Daily API Quota Guardrail
- **Parameter**: `DAILY_PROVIDER_LIMIT = 100`.
- **Reasoning**: Prevents users from exhausting free search-engine API quotas (e.g. Bing / IndexNow daily limits) or triggering Google Cloud Search Console inspection quotas.

### 7. Supabase Database Connection Limits
- **Behavior**: Supabase free tier allows up to 60 direct client connections and 500 MB database storage.
- **Configuration**:
  - Set `DB_SSLMODE=require`.
  - If direct connection limit is ever reached, switch `DB_PORT` from `5432` to Supabase connection pooler port `6543`.

---

## 🛠️ How to Upgrade Limits Later (When Budget Allows)

All guardrail limits are defined in [config/linkpilot.php](file:///d:/Software%20Development/Link%20Insertion%20Tool/config/linkpilot.php) and can be adjusted anytime via environment variables without changing code:

```bash
# Example higher capacity settings on a paid Render / VPS instance:
MAX_URLS_PER_IMPORT=500
MAX_URL_CHECKS_PER_RUN=200
MAX_RETRIES=5
REQUEST_TIMEOUT=30
MAX_RESPONSE_SIZE=10485760 # 10MB
DAILY_PROVIDER_LIMIT=1000
```
