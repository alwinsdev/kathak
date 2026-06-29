# Release Summary — v1.0.0-poc
**Siddha Mudra Therapy** · Functional Proof of Concept · 2026-06-29

The POC objective is met end-to-end: **doctor prescribes a mudra → patient practises with the camera → AI verifies the held gesture → completion is recorded server-side → dashboards reflect it.**

---

## 1. Final Architecture Summary
Layered Laravel 11 with an isolated AI domain. Server-authoritative verification.

```
Browser (Blade + Tailwind + Alpine; modular camera/AI JS, Vite)
   │ HTTPS
Routes → Middleware (auth, verified, role, throttle, CSRF)
   → Controllers (thin: coordinate only)
      → Form Requests (validation + authorization)
      → Actions / Services (business logic)        ← App\Domain\AI for the AI use case
      → Repositories / Eloquent → MySQL
   → Events → Listeners (structured business logging)
RoboflowInferenceClient (server-side, key from config) → Roboflow
Cache (hold state + metrics)   ·   Policies (authorization)   ·   DTOs (typed data)
```
**Principles in force:** SOLID, SoC, thin controllers, DI everywhere, config over constants, DTOs over arrays, policies over ad-hoc checks, `declare(strict_types=1)`. The AI domain is swappable behind `InferenceClient`; verification is pure (`VerifyPracticeAction`) + stateful tracking isolated (`PracticeHoldTracker`); completion is exactly-once.

## 2. Final Folder Structure
```
app/
├── Actions/RegisterPatient.php
├── DTOs/{TodayTherapy,TodaySummary,DueMudra,HistoryStats}.php
├── Domain/AI/
│   ├── Actions/VerifyPracticeAction.php
│   ├── Clients/{RoboflowInferenceClient,FakeInferenceClient}.php
│   ├── Contracts/{InferenceClient,MetricsRecorder}.php
│   ├── DTOs/{InferenceResult,DetectionResult,HoldProgress,MudraPrediction}.php
│   ├── Exceptions/InferenceException.php
│   ├── Metrics/AiMetric.php
│   └── Services/{PracticeSessionService,PracticeHoldTracker,CacheMetricsRecorder}.php
├── Enums/{Role,Gender,PrescriptionStatus,PracticeStatus}.php
├── Events/{PatientRegistered,PrescriptionCreated,PracticeVerified}.php
├── Listeners/{LogPatientRegistered,LogPrescriptionCreated,LogPracticeVerified}.php
├── Http/
│   ├── Controllers/{Auth,Doctor,Patient}/…
│   ├── Middleware/EnsureRole.php
│   └── Requests/{Auth,Doctor,Patient}/…
├── Models/{User,PatientProfile,Mudra,Prescription,PracticeSession}.php
├── Policies/{PrescriptionPolicy,PracticeSessionPolicy,UserPolicy}.php
├── Providers/{AppServiceProvider,AiServiceProvider}.php
└── Repositories/{PrescriptionRepository,PracticeSessionRepository}.php
config/practice.php · database/{migrations,seeders,factories}
resources/js/practice/{camera,detector,overlay,practice}.js
resources/views/{doctor,patient,layouts,components}/…
docs/ (+ docs/archive for superseded planning docs)
```

## 3. Final Database Schema (domain tables)
| Table | Key columns | Notes |
|---|---|---|
| `users` | id, name, email⁰ᵘ, **role**, email_verified_at, password | role enum-backed, indexed |
| `patient_profiles` | id, **user_id**ᶠᵏ ᵘ, **doctor_id**ᶠᵏ ?, age, gender, phone, condition_notes | user→cascade, doctor→nullOnDelete |
| `mudras` | id, name, **slug**ᵘ, description, benefits, **ai_class_label**, reference_image_path, is_active | ai_class_label maps to model class |
| `prescriptions` | id, **patient_id**ᶠᵏ, **doctor_id**ᶠᵏ, **mudra_id**ᶠᵏ, scheduled_time, duration_min, start_date, **end_date**?, notes, **status** | idx (patient_id,status),(doctor_id); patient→cascade, doctor/mudra→restrict |
| `practice_sessions` | id, **prescription_id**ᶠᵏ, **patient_id**ᶠᵏ, practiced_on, started_at, completed_at?, **status**, best_confidence?, detected_class? | idx (patient_id,practiced_on),(prescription_id); cascade |

*(⁰ᵘ unique, ? nullable, ᶠᵏ foreign key. Plus framework tables: sessions, cache, cache_locks, jobs, job_batches, failed_jobs, password_reset_tokens.)*
**Enums:** Role(doctor,patient) · Gender(male,female,other) · PrescriptionStatus(active,completed,expired,cancelled — only `active` used) · PracticeStatus(in_progress,verified,abandoned).

## 4. Final API Summary (domain routes)
**Auth (Breeze):** register, login, logout, password reset/confirm, email verify, profile.

**Doctor** (`auth`, `verified`, `role:doctor`)
| Method | URI | Name |
|---|---|---|
| GET | /doctor/dashboard | doctor.dashboard |
| GET | /doctor/patients/{patient} | doctor.patients.show |
| POST | /doctor/patients/{patient}/prescriptions | doctor.prescriptions.store |
| PUT | /doctor/prescriptions/{prescription} | doctor.prescriptions.update |
| DELETE | /doctor/prescriptions/{prescription} | doctor.prescriptions.destroy |

**Patient** (`auth`, `verified`, `role:patient`)
| Method | URI | Name |
|---|---|---|
| GET | /patient/dashboard | patient.dashboard |
| GET | /patient/prescriptions/{prescription} | patient.prescriptions.show |
| GET | /patient/practice/{prescription} | patient.practice.show |
| POST | /patient/practice/{prescription}/sessions | patient.practice.start |
| POST | /patient/practice/sessions/{session}/detect | patient.practice.detect *(throttled)* |
| GET | /patient/history | patient.history |

All write/JSON endpoints are policy-authorized (cross-tenant → 403) and CSRF-protected. `detect` returns per-frame `{matched, confidence, detected_class, predictions, held_seconds, hold_seconds, verified}`; inference failure → safe `502`.

## 5. Final Test Summary
- **87 tests · 236 assertions · all passing** · Pint clean.
- Unit: VerifyPracticeAction, PracticeHoldTracker (accumulate/reset/grace), PracticeSessionService (exactly-once), RoboflowInferenceClient (Http::fake), PatientScheduleService, PracticeHistoryService, PrescriptionService.
- Feature: auth/registration/roles; doctor prescribing CRUD + panel ownership; patient dashboard/history/detail/practice + ownership; detect endpoint (match/validation/ownership/502 + **inference-failure metric & log**); full AI-workflow frame sequence → verified; idempotency under duplicates; metrics; dashboard reflects completion.
- Live AI loop (camera + real Roboflow) covered by [MANUAL-QA-CHECKLIST.md](MANUAL-QA-CHECKLIST.md).

## 6. Final Project Statistics
| Metric | Value |
|---|---|
| App PHP files | 73 (~2,900 LOC) |
| Models / Controllers / Enums / Policies | 5 / 20 / 4 / 3 |
| AI domain classes | 14 |
| DTOs / Repositories | 8 / 2 |
| Migrations / Seeders / Factories | 8 / 5 / 5 |
| Blade views / JS modules | 38 / 6 |
| Test files / tests / assertions | 22 / 87 / 236 |
| Web routes | 31 |
| Module tags | v0.1.0-l1 → v0.4 (L4 phased) → **v1.0.0-poc** |

---

## 7. Release Summary

### Modules completed
| Module | Scope | Checkpoint |
|---|---|---|
| L1 | Foundation & Auth (Laravel + Breeze, roles, schema base) | v0.1.0-l1 |
| L2 | Prescription Management (doctor panel, prescribe/edit/cancel) | v0.2.0-l2 |
| L3 | Patient Module + architecture refinements (repos, DTOs, policies, events, logging, components) | v0.3.0-l3 |
| L4 | AI Practice & Verification (P1 camera/session, P2 inference/detect, P3 hold/verify/complete) | this release |
| — | Stabilization (docs, cleanup, failure-path test) + Production Readiness Review | **v1.0.0-poc** |

### Features implemented
Doctor panel & prescribing; patient schedule, detail, history; **live AI practice with server-authoritative hold-timer verification and exactly-once completion**; role-based access; structured logging w/ correlation IDs; lightweight metrics; config-driven AI; modular camera/AI JS with hardened lifecycle.

### Test results
87/87 passing (236 assertions); Pint clean; assets build; smoke test green (all patient pages 200; detect validation/short-circuit verified).

### Known limitations
- Live camera→Roboflow verification is **manual QA** (cannot be reliably automated); requires a configured `ROBOFLOW_API_KEY` and good lighting.
- Cache driver is `database` (single-instance); **Redis recommended for production/scale** (RP2).
- `verification_timeout` metric is defined but not yet emitted (no abandon/timeout flow yet).
- No per-prescription threshold/hold overrides; one global config.
- No reminders/notifications, reporting/analytics, or admin role (out of POC scope).
- Session-per-day relies on app-level idempotency (no DB unique constraint — RP3).

### Future enhancement roadmap (post-POC)
1. **RP1** — dedicated `inference` log channel (separate per-frame telemetry from the audit log).
2. **RP2** — Redis cache backend in production (documented in the Deployment Guide).
3. **RP3** — `unique(prescription_id, practiced_on)` to harden session-per-day.
4. Abandon/timeout handling + wire `verification_timeout` metric.
5. Per-prescription threshold/hold overrides; richer scheduling (frequency, multiple times/day).
6. Reminders/notifications; reporting & analytics; optional admin role.
7. PWA/offline; i18n; accessibility pass.

### Freeze
This release is tagged **`v1.0.0-poc`**. From this point, the functional POC is frozen; further work proceeds as the roadmap above under explicit approval.
