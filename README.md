# 🪔 Siddha Mudra Therapy

A proof-of-concept platform where **doctors prescribe Siddha hasta-mudras (hand gestures) as rehabilitation therapy** and **patients practise them at home while a self-hosted AI verifies each gesture live through the camera**. Built on Laravel 11 with a clean, domain-isolated architecture.

> **Status:** Functional POC — `v1.0.0-poc`. The core loop (doctor prescribes → patient practises → AI verifies → completion recorded) is fully implemented and tested.
>
> **Disclaimer:** This is a proof of concept. The AI model has **not** been trained on clinically validated Siddha mudra data, and no claim of medical accuracy is made. The current scope recognizes **Aakash Mudra** only.

---

## Overview

Manual home-exercise programmes are hard to follow and hard to verify. This POC proves that a lightweight, browser-based AI loop can confirm a patient is performing the *prescribed* mudra correctly — turning self-reported adherence into **AI-verified** completion. Verification is **server-authoritative**: the browser only streams camera frames and shows feedback; the backend decides when a session is complete.

## Features

**Doctor**
- Secure login; sees only the patients in their own panel
- Prescribe mudras with a daily schedule (time, duration, start/optional end date, notes)
- Edit (time/duration/notes) or cancel active prescriptions
- View each patient's adherence

**Patient**
- Self-registration with doctor selection
- "Today's Therapy" dashboard (due mudras, completion status)
- Illustrated step-by-step mudra guide (per-step photos, tips, common mistakes)
- **Live AI Practice** — camera stream, server-tracked hold timer, automatic verification (no manual "mark done")
- Practice history with streak and last-practice date

**Platform**
- Role-based access (doctor / patient) via policies + middleware
- Isolated AI domain, server-authoritative verification, exactly-once completion
- Security-hardened per an OWASP-aligned audit — see [.claude/SECURITY_CHECKLIST.md](.claude/SECURITY_CHECKLIST.md)
- Structured logging (correlation IDs) + lightweight operational metrics

## Technology stack

| Layer | Choice |
|---|---|
| Framework | Laravel 11 (PHP 8.2+) |
| Auth | Laravel Breeze (Blade) |
| Database | MySQL 8 / MariaDB 10.4 |
| Frontend | Blade + Tailwind + Alpine.js, Vite |
| AI inference | Self-hosted: MediaPipe hand detector (FastAPI, `:8002`) + YOLOv8 classifier (`:8001`); Roboflow available as an alternative driver |
| Cache | database (POC); **Redis recommended in production** |
| Tests / format | PHPUnit, Laravel Pint, pytest (AI service) |

## AI pipeline

```
browser frame ──► Laravel (auth, rate limit, validation)
                    └──► classifier :8001 ──► detector :8002 (hand crop)
                              └──► YOLOv8 classify ──► {label, confidence}
                    ◄── mapping boundary (internal labels → Siddha names)
server-side hold tracker ──► session verified (atomic, exactly-once)
```

- Both AI services bind to **127.0.0.1 only** and require an `X-API-Key` — they are never exposed to the browser or network.
- The model's internal vocabulary is mapped to Siddha mudra names in one place (`config/services.php`); the UI shows Siddha names only, and any unrecognized pose gets a generic "Incorrect mudra" response.
- Service setup, training, and retraining runbook: [ai-service/training/COMMANDS.md](ai-service/training/COMMANDS.md).

## Folder structure (high level)

```
app/
├── Actions/                 # RegisterPatient
├── DTOs/                    # patient-facing view DTOs (TodayTherapy, HistoryStats, …)
├── Domain/AI/               # isolated AI domain
│   ├── Actions/             #   VerifyPracticeAction (pure)
│   ├── Clients/             #   MediapipeInferenceClient, RoboflowInferenceClient, FakeInferenceClient
│   ├── Contracts/           #   InferenceClient, MetricsRecorder
│   ├── DTOs/                #   InferenceResult, DetectionResult, HoldProgress, MudraPrediction
│   └── Services/            #   PracticeSessionService, PracticeHoldTracker, CacheMetricsRecorder
├── Enums/                   # Role, Gender, PrescriptionStatus, PracticeStatus
├── Events/ · Listeners/     # PatientRegistered, PrescriptionCreated, PracticeVerified (+ log listeners)
├── Http/{Controllers,Requests,Middleware}/   # thin controllers, Form Requests, EnsureRole, SecurityHeaders
├── Models/ · Policies/ · Providers/ · Repositories/
ai-service/                  # self-hosted AI (FastAPI detector + YOLO classifier + training scripts)
config/practice.php          # all AI tunables
database/{migrations,seeders,factories}
resources/js/practice/{camera,detector,overlay,practice}.js   # modular camera/AI JS
resources/views/{doctor,patient,layouts,components}/
docs/                        # architecture, standards, deployment, QA, release
```
Full detail: [docs/PROJECT-STRUCTURE.md](docs/PROJECT-STRUCTURE.md).

## Installation

```bash
git clone <repo> kathak && cd kathak
composer install
npm install
cp .env.example .env
php artisan key:generate

# create the database (MySQL/MariaDB)
mysql -u root -e "CREATE DATABASE siddha_mudra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate --seed
npm run build        # or: npm run dev
php artisan serve    # http://127.0.0.1:8000
```

> Camera access requires **HTTPS** or `http://localhost`.

**AI services** (required for live practice — run each in its own terminal):

```powershell
# 1. Hand detector (:8002) — conda env with Python 3.11 + mediapipe
cd ai-service
$env:API_KEY='change-me'; $env:DETECTION_CONFIDENCE='0.3'
python -m uvicorn app.main:app --host 127.0.0.1 --port 8002

# 2. Classifier (:8001) — env with ultralytics
cd ai-service
python yolo_server.py
```

See [ai-service/training/COMMANDS.md](ai-service/training/COMMANDS.md) for exact environment setup.

## Environment configuration

Key `.env` values (see `.env.example` for all):

```env
APP_NAME="Siddha Mudra Therapy"
DB_CONNECTION=mysql
DB_DATABASE=siddha_mudra
DB_USERNAME=root
DB_PASSWORD=

# Inference provider: mediapipe (self-hosted) | roboflow
INFERENCE_DRIVER=mediapipe

# Self-hosted AI service (server-side only — never exposed to the browser)
MEDIAPIPE_URL=http://localhost:8001
MEDIAPIPE_API_KEY=change-me

# Roboflow (only when INFERENCE_DRIVER=roboflow)
ROBOFLOW_API_KEY=
ROBOFLOW_MODEL_URL=
```

## AI configuration

All AI behaviour is config-driven in [config/practice.php](config/practice.php) (overridable via `.env`):

| Key | Default | Purpose |
|---|---|---|
| `confidence_threshold` | 0.75 | min confidence for a match |
| `hold_seconds` | 3 | fallback hold time — the doctor-prescribed duration wins |
| `detection_interval_ms` | 500 | frame sampling interval |
| `hold_grace_factor` | 4.0 | cap on the time one matched frame may credit |
| `hold_cache_ttl` | 1800 | TTL of cached hold state (refreshed per frame) |
| `smoothing_window` | 5 | recent frames voting on a flickering frame (anti-shake) |
| `smoothing_min_agreement` | 0.6 | window fraction that must show the target to rescue a frame |
| `max_image_kb` | 2048 | max frame upload size |
| `jpeg_quality` | 0.6 | browser JPEG encode quality |
| `detect_rate_limit_per_minute` | 120 | per-user detect throttle |
| `inference_timeout` | 15 | inference HTTP timeout (s) |
| `history_limit` | 20 | recent sessions on history page |

## Demo credentials

After `migrate --seed` (password for all: `password`):

| Role | Email |
|---|---|
| Doctor | `anjali@kathak.test`, `ravi@kathak.test` |
| Patient | `patient@kathak.test` |

## Running tests

```bash
php artisan test          # Laravel suite
./vendor/bin/pint --test  # code style check
cd ai-service && pytest   # AI service suite
```

## Running Vite

```bash
npm run dev     # dev server / HMR
npm run build   # production assets (run after adding new Tailwind classes)
```

## Security

Audited against an enterprise OWASP-aligned Laravel ruleset. Highlights: security headers middleware, auth-route throttling, anti-enumeration on password reset, localhost-only AI services with API key + upload caps, encrypted sessions, policies on every record access. Full rule-by-rule results and the pre-production gate list: [.claude/SECURITY_CHECKLIST.md](.claude/SECURITY_CHECKLIST.md).

## Future roadmap

Deferred enhancements (documented, not in the POC):
- Expand the recognized mudra set beyond Aakash (dataset capture + retraining gate in place)
- Redis cache backend in production (see [Deployment Guide](docs/DEPLOYMENT-GUIDE.md))
- DB uniqueness on `(prescription_id, practiced_on)` to harden session-per-day
- Per-prescription threshold/hold overrides; abandon/timeout handling
- Reminders/notifications; reporting & analytics dashboards; richer scheduling

## Documentation

See [docs/README.md](docs/README.md) for the full documentation index.

## Developer

Developed by **Alwin** — [alwins.dev@gmail.com](mailto:alwins.dev@gmail.com)

## License

Proprietary — proof of concept.
