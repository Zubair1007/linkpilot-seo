# Zero-Budget Deployment Guide: LinkPilot SEO ($0 Total Cost)

This guide walks you through deploying **LinkPilot SEO — Backlink Discovery & Index Monitoring Platform** completely free of charge using:
1. **GitHub** (Free Source Code Hosting)
2. **Supabase** (Free Tier PostgreSQL Database — 500MB storage, 2 active projects)
3. **Render** (Free Web Service — 512MB RAM, 0.1 CPU, `*.onrender.com` subdomain)

---

## 🏗️ Architecture Overview

```
          [ Developer Git Push ]
                     │
                     ▼
           ┌──────────────────┐
           │ GitHub Repository │
           └─────────┬────────┘
                     │ (Webhook / Deploy Hook)
                     ▼
       ┌──────────────────────────┐
       │   Render Free Service    │
       │  (Docker: Nginx+PHP-FPM) │
       │  • Port dynamic binding  │
       │  • React/Vite SPA bundle │
       │  • Database Queue worker │
       └─────────────┬────────────┘
                     │ (SSL encrypted connection)
                     ▼
        ┌────────────────────────┐
        │ Supabase Free Postgres │
        │  • App Data Tables     │
        │  • Sessions & Cache    │
        │  • Queued Jobs         │
        └────────────────────────┘
```

---

## 📋 Step-by-Step Deployment Instructions

### Step 1: Create a GitHub Repository
1. Log into your GitHub account at [github.com](https://github.com).
2. Click **New Repository** (or visit `https://github.com/new`).
3. Repository name: `linkpilot-seo` (or your choice).
4. Select **Private** (recommended to protect your project assets).
5. Do **NOT** initialize with README, .gitignore, or license (these already exist locally).
6. Click **Create repository**.

### Step 2: Push Local Project to GitHub
Open PowerShell or your terminal in the project directory:

```powershell
cd "d:\Software Development\Link Insertion Tool"

# 1. Initialize git if not already initialized
git init

# 2. Stage all files (Notice .env is strictly ignored)
git add .

# 3. Create initial commit
git commit -m "feat: complete LinkPilot SEO platform with production docker and zero-budget configuration"

# 4. Set default branch to main
git branch -M main

# 5. Add your GitHub remote URL (replace YOUR_USERNAME)
git remote add origin https://github.com/YOUR_USERNAME/linkpilot-seo.git

# 6. Push code to GitHub
git push -u origin main
```

---

### Step 3: Create a Free Supabase Project
1. Go to [supabase.com](https://supabase.com) and click **Start your project** (Sign up / Login with GitHub).
2. Click **New project**.
3. Select an organization.
4. **Project Name**: `linkpilot-production`
5. **Database Password**: Set a strong, secure password (⚠️ **Save this password securely**, you will need it in Render).
6. **Region**: Choose the closest region (e.g. `US East (North Virginia)` or `EU Central (Frankfurt)`).
7. **Pricing Plan**: Free ($0/month).
8. Click **Create new project** and wait ~2 minutes for provisioning.

---

### Step 4: Obtain Supabase PostgreSQL Connection Information
1. In your Supabase dashboard, navigate to **Project Settings** (gear icon) -> **Database**.
2. Scroll to **Connection Parameters**:
   - **Host**: e.g., `aws-0-us-east-1.pooler.supabase.com` or `db.[PROJECT_REF].supabase.co`
   - **Port**: `5432` (or `6543` for connection pooling)
   - **Database name**: `postgres`
   - **User**: `postgres` (or `postgres.[PROJECT_REF]` if using pooler)
   - **Password**: The password you chose in Step 3.
3. Keep these values ready for Render.

---

### Step 5: Create a Render Account
1. Visit [render.com](https://render.com) and sign up with your **GitHub account**.
2. Grant Render access to your GitHub repositories when prompted.

---

### Step 6 & 7: Connect Repository & Create Web Service
1. In the Render Dashboard, click **New +** -> **Web Service**.
2. Select **Build and deploy from a Git repository**.
3. Locate `linkpilot-seo` and click **Connect**.
4. Configure the Web Service settings:
   - **Name**: `linkpilot-seo` (your URL will be `https://linkpilot-seo.onrender.com`)
   - **Region**: Same region as your Supabase database (e.g., `Oregon (US West)` or `Frankfurt (EU Central)`).
   - **Branch**: `main`
   - **Runtime**: **Docker** (Render will automatically detect `Dockerfile` in root).
   - **Instance Type**: **Free** ($0/month — 512 MB RAM, 0.1 CPU).

---

### Step 8: Configure Environment Variables in Render
Scroll down to the **Environment Variables** section in Render. Add the following variables:

| Variable Name | Recommended Value | Notes |
|---|---|---|
| `APP_NAME` | `LinkPilot SEO` | Platform title |
| `APP_ENV` | `production` | Enables production hardening |
| `APP_DEBUG` | `false` | **Critical**: Masks stack traces from public users |
| `APP_KEY` | *(Click "Generate" or run `php artisan key:generate --show`)* | 32-character AES encryption key |
| `APP_URL` | `https://linkpilot-seo.onrender.com` | Your assigned Render URL |
| `DB_CONNECTION` | `pgsql` | PostgreSQL driver |
| `DB_HOST` | *Supabase Host* | From Step 4 |
| `DB_PORT` | `5432` | Standard PostgreSQL port |
| `DB_DATABASE` | `postgres` | Default Supabase database name |
| `DB_USERNAME` | `postgres` | (or pooler username if using pooling) |
| `DB_PASSWORD` | *Supabase Password* | From Step 3 |
| `DB_SSLMODE` | `require` | Mandatory for Supabase SSL connection |
| `QUEUE_CONNECTION` | `database` | Uses PostgreSQL `jobs` table (No Redis required) |
| `SESSION_DRIVER` | `database` | Uses PostgreSQL `sessions` table |
| `CACHE_STORE` | `database` | Uses PostgreSQL `cache` table |
| `LOG_CHANNEL` | `stderr` | Streams logs directly to Render log viewer |
| `LOG_LEVEL` | `info` | Production log level |
| `RUN_MIGRATIONS` | `true` | Runs `php artisan migrate --force` on startup |
| `RUN_SEEDER` | `true` | Seeds initial admin/specialist users on first boot |
| `RUN_QUEUE_WORKER` | `true` | Starts embedded queue supervisor worker |
| `MAX_URLS_PER_IMPORT` | `50` | Free-tier import batch limit |
| `MAX_URL_CHECKS_PER_RUN` | `25` | Free-tier crawler limit per run |
| `MAX_RETRIES` | `3` | Free-tier retry cap |
| `REQUEST_TIMEOUT` | `10` | Free-tier cURL timeout in seconds |
| `MAX_RESPONSE_SIZE` | `2097152` | 2MB max download size per page |
| `MAX_CONCURRENT_CHECKS` | `3` | Worker concurrency guardrail |
| `DAILY_PROVIDER_LIMIT` | `100` | Daily external submission cap |

*(Optional: Add `MOZ_ACCESS_ID`, `MOZ_SECRET_KEY`, `AHREFS_API_KEY`, `SEMRUSH_API_KEY` if you have API keys).*

---

### Step 9: Deploy
1. Click **Create Web Service**.
2. Render will start the Docker build:
   - Stage 1 compiles React 19 + TypeScript frontend with Vite.
   - Stage 2 installs PHP 8.2 Alpine, PostgreSQL extensions, Composer dependencies, and sets up Nginx.
3. Watch the build logs in Render dashboard. The build takes ~3 to 4 minutes.

---

### Step 10 & 11: Automatic Migrations & Seeders
Because `RUN_MIGRATIONS=true` and `RUN_SEEDER=true` are configured, the container entrypoint will automatically:
- Run all 8 migration files against Supabase.
- Populate default admin and SEO specialist accounts with sample backlinks and campaigns.
*(Tip: Once the first deployment succeeds, edit the `RUN_SEEDER` environment variable in Render and set it to `false` to avoid re-seeding on future restarts).*

---

### Step 12: Open the Render URL
Once the service shows **Live** in green, click the generated URL (e.g. `https://linkpilot-seo.onrender.com`).

---

### Step 13: Login with Demo Accounts
- **Admin Account**:
  - Email: `admin@linkpilot.io`
  - Password: `password`
- **SEO Specialist Account**:
  - Email: `specialist@linkpilot.io`
  - Password: `password`

---

### Step 14: Verify Health Endpoint (`/health`)
Visit `https://linkpilot-seo.onrender.com/health` in your browser.
You should receive a `200 OK` JSON response:
```json
{
  "status": "ok",
  "app_version": "1.0.0",
  "environment": "production",
  "checks": {
    "database": {
      "status": "healthy",
      "driver": "pgsql",
      "latency_ms": 12
    },
    "queue": {
      "status": "ready",
      "connection": "database",
      "pending_jobs": 0
    }
  }
}
```

---

### Step 15: Verify Database in Supabase
1. In Supabase Dashboard, open **Table Editor**.
2. Verify that tables (`users`, `projects`, `domains`, `campaigns`, `backlinks`, `health_checks`, `jobs`) are populated with seed data.

---

### Step 16: Verify Dashboard & Features
1. Log into LinkPilot UI.
2. Check the **Dashboard** metrics cards (Total Backlinks, Live Rate, Dofollow %, Index Rate).
3. Check the **Backlinks** view and observe the color-coded Ahrefs DR, Moz DA/PA badges.
4. Click **Fetch Metrics** on any backlink to test dynamic authority calculations.
5. Go to **Reports** and click **Export PDF (Executive Report)** to verify PDF generation.

---

### Step 17: Run a Live Backlink Test
1. Go to **URL Health Analyzer** in the left sidebar.
2. Enter a live test target:
   - Source URL: `https://news.ycombinator.com`
   - Target URL: `https://news.ycombinator.com/newsguidelines.html`
3. Click **Run Health Check & Verification**.
4. Confirm:
   - HTTP 200 is detected.
   - Meta tags, robot instructions, and anchor text are extracted safely.
   - No SSRF or memory timeout errors occur.
