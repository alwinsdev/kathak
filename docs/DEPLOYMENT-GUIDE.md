# Deployment Guide — Siddha Mudra Therapy

How to deploy the POC beyond local development. The app is a standard Laravel 11 application; this guide focuses on the production-specific configuration the security/readiness review flagged.

## 1. Requirements
- PHP **8.2+** with: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `bcmath`
- Composer 2.x, Node 18+/npm (build only), MySQL 8 / MariaDB 10.4+
- A web server (Nginx/Apache) pointing at the **`public/`** directory
- HTTPS (required for camera access and secure cookies)
- *(Recommended)* Redis for cache (see §5)

## 2. Build & migrate
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force          # --force is required in production
php artisan db:seed --force          # optional: demo doctor/mudra data
php artisan config:cache route:cache view:cache
```
Re-run `npm run build` whenever Blade introduces new Tailwind classes.

## 3. Production environment (`.env`)
```env
APP_ENV=production
APP_DEBUG=false                      # never true in production
APP_URL=https://your-domain
APP_KEY=base64:...                   # php artisan key:generate

# Secure session/cookies (requires HTTPS)
SESSION_DRIVER=database              # or redis
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Least-privilege DB user (NOT root)
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=siddha_mudra
DB_USERNAME=smt_app
DB_PASSWORD=<strong-secret>

# Roboflow (server-side only)
ROBOFLOW_API_KEY=<secret>
ROBOFLOW_MODEL_URL=https://serverless.roboflow.com/<model>/<version>

# Cache (see §5)
CACHE_STORE=redis
```

## 4. Security checklist (production)
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Served exclusively over **HTTPS** (HSTS recommended)
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] **Least-privilege DB user** (not `root`, not blank password)
- [ ] `ROBOFLOW_API_KEY` set via secret manager / env, never committed
- [ ] `php artisan config:cache` so `.env` isn't read per request
- [ ] Detect rate limit (`practice.detect_rate_limit_per_minute`) tuned for expected load/cost
- [ ] File/storage permissions: `storage/` and `bootstrap/cache/` writable by the web user only

## 5. Cache backend (recommended: Redis)
The POC ships with `CACHE_STORE=database`, which is correct for a single instance. The AI **hold tracker** and **metrics** write to the cache at frame rate.

For production — especially multiple app instances — use **Redis**:
```env
CACHE_STORE=redis
REDIS_HOST=...
REDIS_PORT=6379
```
This is the one change required for safe horizontal scaling: hold state must live in a **shared** store so any instance can serve a patient's frames. No code change is needed (the app uses the cache abstraction throughout). *(Deferred item RP2.)*

## 6. Logging & metrics
- Business/audit events log to the **`business`** daily channel (`storage/logs/business-*.log`) with correlation IDs.
- Per-frame inference telemetry currently shares that channel; a dedicated `inference` channel is a documented future enhancement (RP1). At high frame volume, ship logs to a centralized aggregator and/or lower retention.
- Operational counters (`metrics:ai:*`) live in the cache; expose/scrape them later as needed.

## 7. Inference / model notes
- Verification quality depends on the Roboflow model and camera/lighting — exercise the [Manual QA Checklist](MANUAL-QA-CHECKLIST.md) after deploy.
- The model's class labels must match each mudra's `ai_class_label` (seeded). Update via the `mudras` table if the model changes.

## 8. Smoke test after deploy
1. Log in as a doctor → prescribe a mudra to a patient.
2. Log in as that patient → open Practice → allow camera → hold the mudra → confirm auto-verification and dashboard "Done".
3. Confirm the Network tab shows **no** Roboflow URL/key (only same-origin `/patient/practice/.../detect`).

## 9. Known future-enhancement flags
| Ref | Item | Status |
|---|---|---|
| RP1 | Dedicated inference log channel | Deferred |
| RP2 | Redis cache in production | **Documented here** (config-only) |
| RP3 | `unique(prescription_id, practiced_on)` schema constraint | Deferred (no schema change post-POC) |
