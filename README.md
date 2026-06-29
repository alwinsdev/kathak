# 🪔 Siddha Mudra Therapy

A proof-of-concept platform where **doctors prescribe Siddha hasta-mudras (hand gestures) as rehabilitation therapy** and **patients practise them at home while AI verifies each gesture in real time**. Built on Laravel 11 with a clean, domain-isolated architecture.

> **Status:** Functional POC — `v1.0.0-poc`. The core objective (doctor prescribes → patient practises → AI verifies → completion recorded) is fully implemented and tested.

---

## Overview

Manual home-exercise programmes are hard to follow and hard to verify. This POC proves that a lightweight, browser-based AI loop can confirm a patient is performing the *prescribed* mudra correctly — turning self-reported adherence into **AI-verified** completion. Verification is **server-authoritative**: the browser only streams camera frames and shows feedback; the backend decides when a session is complete.

## Features

**Doctor**
- Secure login; sees only the patients in their own panel
- Prescribe one or more mudras with a daily schedule (time, duration, start/optional end date, notes)
- Edit (time/duration/notes) or cancel active prescriptions
- View each patient's adherence

**Patient**
- Self-registration with doctor selection; consent-free POC scope
- "Today's Therapy" dashboard (due mudras, completion status)
- Prescription detail view
- **Live AI Practice** — camera + Roboflow detection, server-tracked hold timer, automatic verification (no manual "mark done")
- Practice history with streak and last-practice date

**Platform**
- Role-based access (doctor / patient) via policies + middleware
- Isolated AI domain, server-authoritative verification, exactly-once completion
- Structured logging (correlation IDs) + lightweight operational metrics

## Technology stack

| Layer | Choice |
|---|---|
| Framework | Laravel 11 (PHP 8.2+) |
| Auth | Laravel Breeze (Blade) |
| Database | MySQL 8 / MariaDB 10.4 |
| Frontend | Blade + Tailwind + Alpine.js, Vite |
| AI inference | Roboflow serverless model (server-side proxy) |
| Cache | database (POC); **Redis recommended in production** |
| Tests / format | PHPUnit, Laravel Pint |

## Folder structure (high level)

```
app/
├── Actions/                 # RegisterPatient
├── DTOs/                    # patient-facing view DTOs (TodayTherapy, HistoryStats, …)
├── Domain/AI/               # isolated AI domain
│   ├── Actions/             #   VerifyPracticeAction (pure)
│   ├── Clients/             #   RoboflowInferenceClient, FakeInferenceClient
│   ├── Contracts/           #   InferenceClient, MetricsRecorder
│   ├── DTOs/                #   InferenceResult, DetectionResult, HoldProgress, MudraPrediction
│   ├── Exceptions/          #   InferenceException
│   ├── Metrics/             #   AiMetric
│   └── Services/            #   PracticeSessionService, PracticeHoldTracker, CacheMetricsRecorder
├── Enums/                   # Role, Gender, PrescriptionStatus, PracticeStatus
├── Events/ · Listeners/     # PatientRegistered, PrescriptionCreated, PracticeVerified (+ log listeners)
├── Http/{Controllers,Requests,Middleware}/   # thin controllers, Form Requests, EnsureRole
├── Models/ · Policies/ · Providers/ · Repositories/
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

> Camera + browser notifications require **HTTPS** or `http://localhost`.

## Environment configuration

Key `.env` values (see `.env.example` for all):

```env
APP_NAME="Siddha Mudra Therapy"
DB_CONNECTION=mysql
DB_DATABASE=siddha_mudra
DB_USERNAME=root
DB_PASSWORD=

# Roboflow (server-side only — never exposed to the browser)
ROBOFLOW_API_KEY=
ROBOFLOW_MODEL_URL=https://serverless.roboflow.com/kathak-trainer/8
```

## AI configuration

All AI behaviour is config-driven in [config/practice.php](config/practice.php) (overridable via `.env`):

| Key | Default | Purpose |
|---|---|---|
| `confidence_threshold` | 0.75 | min confidence for a match |
| `hold_seconds` | 5 | how long the correct mudra must be held |
| `detection_interval_ms` | 1000 | frame sampling interval |
| `hold_grace_factor` | 2.5 | jitter tolerance before the hold restarts |
| `hold_cache_ttl` | 300 | TTL of cached hold state |
| `max_image_kb` | 2048 | max frame upload size |
| `jpeg_quality` | 0.7 | browser JPEG encode quality |
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
php artisan test          # full suite
./vendor/bin/pint --test  # code style check
```

## Running Vite

```bash
npm run dev     # dev server / HMR
npm run build   # production assets (run after adding new Tailwind classes)
```

## Screenshots

_Add screenshots here:_
- `docs/screenshots/doctor-dashboard.png`
- `docs/screenshots/prescribe.png`
- `docs/screenshots/patient-today.png`
- `docs/screenshots/practice-live.png`
- `docs/screenshots/history.png`

## Future roadmap

Deferred enhancements (documented, not in the POC):
- **RP1** — dedicated `inference` log channel (separate high-volume telemetry from the audit log)
- **RP2** — Redis cache backend in production (see [Deployment Guide](docs/DEPLOYMENT-GUIDE.md))
- **RP3** — DB uniqueness on `(prescription_id, practiced_on)` to harden session-per-day
- Per-prescription threshold/hold overrides; abandon/timeout handling + `verification_timeout` metric
- Reminders/notifications; reporting & analytics dashboards; richer scheduling

## Documentation

See [docs/README.md](docs/README.md) for the full documentation index.

## License

Proprietary — proof of concept.
