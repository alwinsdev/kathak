# 03 — Technical Design Document (TDD)
**Project:** Siddha Mudra Therapy Platform
**Version:** 1.0 (Draft for approval) · **Date:** 2026-06-25

Covers: target architecture (§2), database design & migrations (§3), backend architecture (§4), frontend architecture (§5), API endpoints (§6), security design (§7), non-functional (§8).

---

## 1. Guiding principles
Layered, testable, secure-by-default. Thin controllers → service layer (business rules) → repository/ORM. No business logic in views. Every state change is authorized, validated, CSRF-protected, and audit-logged. Backwards-compatible, **incremental migration** (strangler-fig) so the live MVP keeps working module by module.

## 2. Target architecture & recommendation

### 2.1 Recommended: Laravel 11 (PHP 8.3), MySQL 8, Redis, queue worker
```
                ┌──────────────────────────────────────────────┐
 Browser/PWA →  │  Laravel (HTTP)                               │
  (Blade +      │  Routes → Middleware(auth, role, csrf, throttle)│
   Alpine.js +  │        → Controllers → FormRequests(validate) │
   camera JS)   │        → Services(domain) → Eloquent(repos)   │
                │        → Events → Listeners                   │
                └───────┬───────────────┬─────────────┬─────────┘
                        │               │             │
                   MySQL 8         Redis (cache,   Queue worker
                 (clinical data)   sessions,       (reminders: mail,
                                   rate-limit)      push) + Scheduler
                        │
                 Inference proxy controller → Roboflow (server-side key)
```
**Why (vs. hand-rolled):** auth, hashing, sessions, CSRF, validation, RBAC middleware, migrations, queue/scheduler, mailer, rate-limiting all come built-in and audited — directly retiring defects C4–C7, C11, C13. Strong testing story (PHPUnit/Pest).

### 2.2 Alternative: harden the current procedural app
Front-controller (`public/index.php`) + PSR-4 autoload + a thin MVC (Router, Controller, Service, Repository), hand-rolled CSRF middleware, `vlucas/phpdotenv`, `monolog`, a cron-driven reminder script. Viable but re-implements vetted framework plumbing; choose only if Composer/Laravel is barred on the host.

> **Both plans share the same module sequence and data model.** Effort estimates in `04` assume Laravel; add ~25–40% to security/infra tasks if harden-in-place is chosen.

## 3. Database design

### 3.1 Schema changes (additive, migration-based)

**Evolve existing**
- `users`: add `role` value **`admin`**; add `email_verified_at`, `status` (active/disabled), `last_login_at`.
- `mudras`: add `category`, `difficulty`, `ai_class_label` (unique nullable), `reference_image_path`, `reference_video_url`, `status` (active/retired), `created_by`.
- `assignments`: keep as the prescription header; add `frequency` (daily/weekly/interval), `weekdays` (JSON/bitmask, nullable), `interval_days` (nullable), `reps`, `sets`, `start_date`, `end_date` (nullable), `deactivated_at`. Keep `active` for compatibility during migration.
- `completions`: add `source` (**ai_verified / manual_override** — policy D3), `practice_session_id` (nullable), `confidence` (nullable), `override_by` (admin user, nullable, set only for `manual_override`), make per **schedule-slot** not just per-assignment-day (see `assignment_schedules`).

**New tables**
| Table | Purpose | Key columns |
|---|---|---|
| `patient_doctor` | Panel ownership (BR1) | `patient_id`, `doctor_id`, `assigned_by`, `assigned_at`, `active` |
| `assignment_schedules` | Per-time-of-day slots for a prescription | `assignment_id`, `time_of_day`, `active` |
| `schedule_snapshots` | Daily expected-schedule snapshot for accurate adherence (BR5) | `patient_id`, `snapshot_date`, `expected_count`, `detail(JSON)` |
| `practice_sessions` | One AI/manual practice attempt | `assignment_id`, `patient_id`, `started_at`, `ended_at`, `target_class`, `best_confidence`, `verified(bool)`, `frames_evaluated` |
| `notifications` | Server-side reminders/feedback delivery | `user_id`, `type`, `channel`, `payload(JSON)`, `scheduled_for`, `sent_at`, `read_at` |
| `messages` | Doctor↔patient feedback thread | `from_user_id`, `to_user_id`, `body`, `read_at`, `created_at` |
| `consents` | Consent capture | `user_id`, `version`, `accepted_at`, `ip` |
| `password_resets` | Forgot-password tokens | `email`, `token_hash`, `expires_at`, `used_at` |
| `audit_logs` | Tamper-evident trail (BR6) | `actor_id`, `action`, `entity`, `entity_id`, `before(JSON)`, `after(JSON)`, `ip`, `created_at` |
| `settings` | System config (thresholds, channels, policy) | `key`, `value(JSON)` |
| `failed_logins` | Throttle/lockout (C7) | `email`, `ip`, `attempts`, `locked_until` |

### 3.2 Adherence accuracy (fix C9)
A daily scheduler writes a `schedule_snapshots` row per patient capturing **what was expected that day** from then-active `assignment_schedules`. Adherence = completed slots ÷ snapshot expected slots over the window. This removes the back-projection bug and supports per-slot (not just per-day) granularity.

### 3.3 Integrity & indexing
FKs with sensible `ON DELETE` (clinical tables use soft-delete, not cascade). Composite indexes: `completions(assignment_id, completed_date)` (exists, keep), `patient_doctor(doctor_id, active)`, `practice_sessions(patient_id, started_at)`, `audit_logs(entity, entity_id)`, `notifications(user_id, scheduled_for, sent_at)`. Money/PHI columns considered for at-rest encryption (app-level for notes/phone if required).

## 4. Backend architecture
- **Routing/Controllers:** one controller per resource (Auth, Patient, Doctor, Admin, Mudra, Prescription, Practice, Adherence, Message, Notification). Controllers stay thin.
- **FormRequests:** all validation rules from FRS §6 declared here (server-authoritative).
- **Middleware:** `auth`, `role:patient|doctor|admin`, `verified`, `throttle`, `csrf`, `owns-patient` (enforces BR1 panel ownership — directly fixes C8), `audit`.
- **Services (domain):** `PrescriptionService`, `AdherenceService`, `PracticeVerificationService`, `ReminderService`, `ConsentService`, `MudraService`. All business rules (BR1–BR7) live here, unit-tested.
- **Inference proxy:** `PracticeController@detect` — auth + ownership + rate-limit + size/type validation, then server-side call to Roboflow with the key from env (fixes C3, C4). Returns normalized predictions.
- **Jobs/Scheduler:** `SnapshotDailySchedules` (nightly), `FlagNonAdherence` (nightly), `RaiseDueReminders` (per-minute scheduler → writes **in-app** `notifications` rows only — policy D4; email/push channels deferred). A queue worker is still provisioned (D1) for these jobs and future channels.
- **Events:** `PrescriptionCreated`, `SessionVerified`, `PatientAssigned` → listeners for notifications + audit.

## 5. Frontend architecture
- **Server-rendered Blade** (keeps current simplicity) + **Alpine.js** for interactivity + small vanilla modules for camera/WebRTC and the detection loop. Chart.js retained.
- Extract the inline `<style>` block into a shared stylesheet + design tokens (reuse the existing CSS variables). Component partials: nav, stat-card, table, alert, modal, form-field.
- **PWA**: manifest + service worker for installability and Web Push (server-side reminders). Camera/notifications gated behind HTTPS (already noted in README).
- Accessibility pass: labels/`aria`, color-contrast on badges, keyboard nav.

## 6. API endpoints (RESTish; CSRF-protected forms or token API)

**Auth**
```
POST   /register                 patient self-signup (+consent)
POST   /login                    throttled
POST   /logout
POST   /password/forgot          enumeration-safe
POST   /password/reset           token + new password
GET    /email/verify/{id}/{hash} signed URL
```
**Patient**
```
GET    /patient/dashboard            today's due sessions, feedback, banners
POST   /patient/sessions/{slot}/complete   manual completion
GET    /patient/history              time-accurate adherence + heatmap
GET    /patient/profile  | PUT /patient/profile
POST   /practice/detect              auth+owns+throttle → Roboflow proxy
POST   /practice/sessions            create/finalize a practice_session (verified)
GET    /patient/messages | POST /patient/messages  (reply)
```
**Doctor**
```
GET    /doctor/dashboard             own-panel patients + flags
GET    /doctor/patients/{id}         owns-patient guarded
GET    /doctor/patients/{id}/prescriptions
POST   /doctor/patients/{id}/prescriptions        create (rich schedule)
DELETE /doctor/prescriptions/{id}                 soft-deactivate
GET    /doctor/patients/{id}/adherence            accurate report
GET    /doctor/patients/{id}/adherence/export?fmt=pdf|csv
POST   /doctor/patients/{id}/messages             feedback
```
**Admin**
```
GET    /admin/dashboard
GET/POST/PATCH  /admin/doctors            create/disable doctor
GET/POST        /admin/assignments        patient→doctor ownership
GET/POST/PUT/DELETE /admin/mudras         library CRUD (+media upload)
GET/PUT         /admin/settings           thresholds/channels/policy
GET             /admin/audit-logs
```
All write endpoints: validate (FormRequest) → authorize (policy/middleware) → service → audit. JSON endpoints return `{data, meta}` / `{error:{code,message,fields}}`.

## 7. Security design (maps to defects)
- **Secrets** → `.env` + `.gitignore`; remove `make_hash.php`, `fix_doctor.php` (C1, C2, C4).
- **Inference proxy** auth+ownership+rate-limit+MIME/size caps (C3).
- **CSRF** middleware on all forms; **session** regen on login + `HttpOnly/Secure/SameSite=Lax` cookies (C5, C6).
- **Brute-force**: `throttle` + `failed_logins` lockout + optional captcha (C7).
- **RBAC ownership** via `owns-patient` policy (C8).
- **Audit** middleware/listeners on clinical writes (C13/BR6).
- Password reset hardened (WF-4); email verification; least-privilege DB user; HTTPS/HSTS; security headers (CSP, X-Frame-Options, etc.); dependency scanning.

## 8. Non-functional requirements
| Attribute | Target |
|---|---|
| Performance | Dashboard TTFB < 300 ms p95 (excl. inference); inference round-trip bounded by Roboflow |
| Availability | 99.5% app; graceful degradation if inference down (manual completion) |
| Scalability | Stateless app behind LB; Redis sessions; queue workers scale horizontally |
| Security | OWASP ASVS L2 targets; secrets in env; audit trail |
| Privacy | Consent, RBAC ownership, data-retention setting, export/delete on request |
| Observability | Structured logs (Monolog), error tracking, queue/inference health on admin dashboard |
| Testing | Unit (services), feature (HTTP), parity tests per migrated module; CI gate |
| Backup/DR | Nightly DB backup + restore drill; migrations reversible |
