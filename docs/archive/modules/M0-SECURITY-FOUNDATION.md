# Module M0 — Security & Foundation — Detailed Design
**Status:** Awaiting sign-off (no code until approved) · **Date:** 2026-06-25
**Locked decisions (2026-06-25):** Laravel 11 migration · India DPDP baseline · **AI-verified-only** completion (admin override for outages) · **In-app-only** reminders. Full register: `04-IMPLEMENTATION-PLAN.md §6`. *None of these affect M0 scope* — M0 is stack + security foundation only; the completion-policy and reminder choices bite in M6/M7.

Delivers features **F1–F3**. This is the prerequisite module: it closes every active security hole and lays the framework, RBAC, audit, and migration foundation that all later modules build on. Strategy: **strangler-fig** — the new Laravel app is stood up alongside, achieves parity with today's flows, then becomes the live entry point.

---

## 1. Goals & exit criteria
**Goal:** A secure Laravel foundation at parity with the current MVP — patients/doctors can register, log in, view dashboards, prescribe, mark done, run AI practice — with all P0 defects (C1–C8, C13) closed.

**Exit criteria (Definition of Done):**
- [ ] `fix_doctor.php` and `make_hash.php` deleted; no backdoors in webroot.
- [ ] All secrets in `.env`; `.env.example` committed; `.gitignore` excludes `.env`, `/vendor`, uploads, logs.
- [ ] Inference proxy requires auth + ownership, validates MIME/size, rate-limited; Roboflow key server-side only.
- [ ] CSRF on all forms; session regen on login; `HttpOnly/Secure/SameSite=Lax` cookies.
- [ ] Login throttled + lockout after N failures.
- [ ] Security headers (CSP, HSTS in prod, X-Frame-Options, X-Content-Type-Options, Referrer-Policy).
- [ ] `users/patients/mudras/assignments/completions` migrated (parity with `schema.sql`); mudras + initial admin seeded via console command.
- [ ] `admin` role exists; RBAC middleware enforces patient/doctor/admin.
- [ ] Audit log records clinical writes; structured app logging (Monolog) on.
- [ ] Parity tests green for existing patient & doctor journeys.

## 2. Scope
**In:** Laravel skeleton, env/secrets, migrations+seeders for existing tables (+`admin` role, `audit_logs`, `failed_logins`, `settings`), auth (login/register/logout/profile shell), RBAC + CSRF + throttle + audit middleware, secured inference proxy, base Blade layout ported from current CSS, logging, security headers, parity tests, local HTTPS guidance.
**Out (later modules):** forgot/reset + email verify (M1), panel ownership/consent (M2), rich scheduling, accurate adherence, AI verification, reminders. M0 keeps **current behavior** for these (e.g., doctor still sees all patients until M2 — but now behind real auth/audit).

## 3. Target project structure
```
kathak/                         (new Laravel root; legacy files retired as parity is reached)
├── app/
│   ├── Http/
│   │   ├── Controllers/{Auth,Patient,Doctor,Admin,PracticeController}.php
│   │   ├── Middleware/{EnsureRole, AuditClinicalWrite, SecurityHeaders}.php
│   │   └── Requests/{LoginRequest, RegisterPatientRequest}.php
│   ├── Models/{User, Patient, Mudra, Assignment, Completion, AuditLog, Setting}.php
│   ├── Services/{AuthService, InferenceService, AuditService}.php
│   └── Console/Commands/{CreateAdmin, SeedMudras}.php
├── config/  routes/{web.php}  database/migrations  database/seeders
├── resources/views/{layouts/app.blade.php, auth, patient, doctor}
├── public/  (web root → index.php front controller; camera/JS assets)
├── .env.example   .gitignore
└── tests/Feature/{AuthTest, RbacTest, InferenceProxyTest, ParityTest}.php
```

## 4. Data/migrations in M0
- Migrations mirroring `schema.sql` (users, patients, mudras, assignments, completions) — **same columns/keys** for parity; `completions.uniq_assign_date` preserved.
- `users`: add `role` enum value **admin**, `status` (active/disabled, default active), `last_login_at`.
- New: `audit_logs` (actor_id, action, entity, entity_id, before JSON, after JSON, ip, ua, created_at), `failed_logins` (email, ip, attempts, locked_until), `settings` (key, value JSON) — seeded with M0 defaults (throttle limits, security toggles).
- Seeders: 10 mudras (from `schema.sql`), and `php artisan app:create-admin` console command (interactive/env-driven) — **replaces** the deleted backdoor for bootstrapping the first admin. Existing demo doctor seeded with a freshly hashed password.

## 5. Security implementation detail (defect → control)
| Defect | Control in M0 |
|---|---|
| C1 backdoor reset | Delete `fix_doctor.php`; password resets only via M1 token flow; admin bootstrap via signed console command |
| C2 hash dumper | Delete `make_hash.php` |
| C3 open proxy | `PracticeController@detect`: `auth` + `role:patient` + (owns-target in M2; M0 = own-session) + `throttle:practice` + MIME(jpeg/png)+≤2 MB + dimension cap; Roboflow call server-side from `InferenceService` using `config('services.roboflow.key')` |
| C4 secrets | `.env` (`DB_*`, `ROBOFLOW_KEY`, `ROBOFLOW_MODEL_URL`); `.gitignore`; least-privilege DB user documented |
| C5 CSRF | Laravel `VerifyCsrfToken` on all web POST/PUT/DELETE; `@csrf` in forms |
| C6 sessions | `session.regenerate()` on login; cookie `http_only=true, secure=true(prod), same_site=lax`; Redis/db session driver |
| C7 brute force | `throttle:login` (e.g. 5/min/ip+email) + `failed_logins` lockout (e.g. 10 fails → 15-min lock); generic error message |
| C8 ownership | M0 ships RBAC role gates; **full panel ownership lands in M2** (flagged, not silently skipped) |
| C13 audit/logging | `AuditClinicalWrite` middleware + `AuditService` on prescribe/complete/login; Monolog daily logs; `SecurityHeaders` middleware |

## 6. Inference proxy contract (replaces `predict.php`)
```
POST /practice/detect          (auth: patient; throttle:practice; csrf-exempt API token OR same-origin)
  body: multipart/form-data { image: <jpeg|png ≤2MB> }
  → 200 { data: { predictions: [{class, confidence, x, y, width, height}], inference_ms } }
  → 401 unauth · 413 too large · 415 bad type · 429 rate-limited · 502 upstream error (key never leaked)
```

## 7. Task list (development order)
1. Initialize Laravel 11 project in repo; configure app, MySQL, session (db/Redis), Monolog.
2. `.gitignore` + `.env.example`; move DB + Roboflow secrets to `.env`/`config/services.php`.
3. **Delete `fix_doctor.php`, `make_hash.php`, `predict.php`, `config.php`** (functionality re-homed); retire legacy page files as parity is reached.
4. Migrations for the 5 existing tables (parity) + `admin` role/status + `audit_logs` + `failed_logins` + `settings`.
5. Seeders: mudras + demo doctor; `app:create-admin` console command (admin bootstrap).
6. Base Blade layout `layouts/app` from current header/footer CSS (extract inline `<style>` once, reuse tokens).
7. Auth: register (patient), login (role-aware, server-authoritative), logout; hashing; session regen; secure cookies.
8. Middleware: `EnsureRole`, `SecurityHeaders`, throttle config, CSRF (built-in), `AuditClinicalWrite`.
9. `failed_logins` lockout in `AuthService`.
10. Port patient dashboard / mark-done, doctor dashboard / assign / adherence, patient practice + history to Blade (behavior parity — no new features yet).
11. Secure inference proxy (`PracticeController@detect` + `InferenceService`).
12. Feature tests: AuthTest, RbacTest, InferenceProxyTest (401/413/415/429), ParityTest (patient & doctor happy paths).
13. README/runbook update: env setup, `create-admin`, local HTTPS for camera/notifications, deploy notes.

## 8. Estimate & risks
- **Effort:** F1 1–2d + F2 2–3d + F3 5–8d ≈ **8–13 ideal dev-days**.
- **Complexity:** High (foundation).
- **Risks:** (a) Parity regressions — mitigated by ParityTest before retiring legacy files; (b) Camera needs HTTPS — document local cert/`localhost`; (c) Roboflow response shape variance — normalize in `InferenceService` with a contract test.
- **Dependencies:** none upstream; **blocks all later modules**.

## 9. Sign-off
On approval of this design I will implement M0 to the exit criteria above, then demo parity + the closed defects before proposing **M1 (Admin & Identity)**.
```
Approve M0 build?  ▢ Approve as-is   ▢ Approve with changes (note below)   ▢ Discuss
```
