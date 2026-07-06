# Security Checklist — Siddha Mudra Therapy (POC)

**Audited:** 2026-07-06 · against *Enterprise Laravel Security Rules v3.0* (OWASP-aligned)
**Scope:** Laravel app + local AI services (`:8001` classifier, `:8002` detector)
**Status legend:** ✅ pass · 🔧 fixed in this audit · 🚧 production gate (must do before any real deployment) · ➖ not applicable to this POC

---

## Rule 1 — Application Basics
- 🚧 `APP_DEBUG=false` + `APP_ENV=production` — currently `local/true` (dev machine). **Hard gate before deployment.**
- ✅ `APP_KEY` generated; `.env` gitignored and not tracked; no secrets in source.
- ✅ `composer audit`: 0 advisories · `npm audit`: 0 vulnerabilities (2026-07-06).
- 🚧 File permissions (775/664, `.env` 640) — host-dependent; apply on the deployment target.

## Rule 2 — Cookies & Session
- ✅ `http_only=true`, `same_site=lax` (Laravel defaults confirmed in config).
- 🔧 `SESSION_ENCRYPT=true` set (.env + .env.example).
- ✅ Cookie encryption middleware active (Laravel 11 default stack).
- 🚧 `SESSION_LIFETIME=120` — acceptable for POC; reduce (≤30) for production healthcare data.
- ✅ Sensitive profile deletion requires password confirmation (Breeze confirm-password flow).

## Rule 3 — Authentication
- ✅ Official starter kit (Laravel Breeze) — no custom auth.
- ✅ bcrypt hashing (Laravel default, cost 12); `BCRYPT_ROUNDS=12`.
- ✅ Login lockout: 5 attempts per email+IP (Breeze `LoginRequest`).
- 🔧 Route-level throttles added: login 10/min/IP, register 6/min, forgot-password 3/min, reset-password 6/min (verification routes were already throttled).
- 🔧 Enumeration guard: forgot-password now always returns a generic message regardless of account existence.
- ➖ 2FA — out of POC scope; recommended (Fortify) before production.
- 🚧 Email verification not enforced for patient accounts — decide before production.

## Rule 4 — Authorization
- ✅ Policies everywhere: `PrescriptionPolicy`, `PracticeSessionPolicy`, `UserPolicy@manage`; controllers use `Gate::authorize`/Form-Request `authorize()`.
- ✅ The camera detect endpoint authorizes via `DetectFrameRequest` (`can('update', $session)`) — not just middleware.
- ✅ Role separation enforced by `EnsureRole` middleware + per-record policies (doctor ↔ own panel, patient ↔ own data).
- ✅ No UI-only "security" found; every mutating route re-checks server-side.
- ➖ Maker-checker workflows — no financial operations in this app.

## Rule 5 — Mass Assignment
- ✅ No `$request->all()` into models, no `unguard()`, no empty `$guarded`.
- ✅ Controllers persist via `$request->validated()` through services.
- ✅ Scoping IDs (`patient_id`, `doctor_id`) always derived from the authenticated session, never the request.
- ✅ State fields (`status`, `best_confidence`, completion columns) set only by domain services.

## Rule 6 — SQL Injection
- ✅ 100% Eloquent/query-builder; zero raw SQL in `app/`.
- ✅ No user-controlled column names (no dynamic `orderBy` from input).

## Rule 7 — XSS
- ✅ Zero `{!! !!}` in all Blade views; everything auto-escaped.
- ✅ JS receives data via `@js()` / data-attributes — no string-interpolated `<script>` vars.
- 🚧 Content-Security-Policy — intentionally not set: Alpine (standard build) needs `unsafe-eval`, Vite injects inline assets. Production path: Alpine CSP build + nonce'd CSP. Other headers are in place (see Rule 17).

## Rule 8 — CSRF
- ✅ Laravel 11 default CSRF stack; **zero exclusions** configured.
- ✅ Every mutating form carries `@csrf` (all form views verified).
- ✅ AJAX detect loop sends `X-CSRF-TOKEN` from the meta tag; 419 handled gracefully in UI.

## Rule 9 — File Uploads
- ✅ Practice frames: `mimetypes:image/jpeg,image/png` + size cap from config (`DetectFrameRequest`).
- ✅ Uploads are processed in memory and forwarded to the AI service — never stored to disk, never in `public/`.
- 🔧 AI classifier now rejects bodies > 5 MB (DoS guard) in addition to Laravel-side limits.
- ✅ No user-controlled filenames/paths anywhere.

## Rule 10 — Path Traversal / Open Redirect
- ✅ No `response()->download()` with user paths; no redirects from request input.

## Rule 11 — Command/Object Injection
- ✅ No `exec/eval/unserialize/extract/shell_exec` in the application.

## Rule 12 — Rate Limiting
- ✅ `practice-detect` limiter (config-driven, per user) on the AI detect route.
- 🔧 Auth surface fully covered now (see Rule 3).
- ➖ Export/settlement limiters — no such endpoints.

## Rules 13, 21 — Financial Integrity / Exports
- ➖ No financial operations, no data exports in this app.
- ✅ (Analogous invariant) Session completion is server-authoritative: hold timing tracked in server cache, completion via atomic conditional UPDATE (`in_progress → verified`), exactly-once event — client cannot forge completion.

## Rule 14 — IDOR / Isolation
- ✅ Every patient/doctor record access flows through a policy that checks ownership.
- ✅ Cross-patient access covered by tests (`assertForbidden` cases in the suite).
- 🚧 Sequential integer IDs are exposed in URLs (`/patient/practice/23`) — authorization makes this safe, but UUIDs are recommended before production.

## Rule 15 — Audit / Logging
- ✅ Structured `business` log channel: registrations, prescriptions (create/update/cancel), inference start/success/failure, verifications — with correlation IDs.
- ✅ No passwords/tokens/secrets in logs; **no landmark/biometric coordinates logged** (by design).
- 🚧 Production: centralize logs; add auth-failure logging + alerting.

## Rule 16 — Queues/Jobs
- ➖ No queued jobs in this app (sync flows only).

## Rule 17 — Security Headers & Infra
- 🔧 `SecurityHeaders` middleware added to the web group: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(self), microphone=(), geolocation=(), payment=()` (camera allowed for the practice screen only), HSTS in production.
- ✅ CORS: Laravel defaults (no wildcard API exposure configured).
- 🚧 HTTPS termination, encrypted backups, SIEM — deployment-time items.

## Rule 18 — Secrets
- ✅ `.env`/`.env.*` gitignored (only `.env.example` tracked, placeholder values).
- ✅ Roboflow/MediaPipe keys live in `.env` only; nothing in source or frontend bundles.
- 🚧 `API_KEY=change-me` for the internal AI services is a placeholder — generate a random key before any shared deployment (mitigated today by localhost-only binding).

## Rule 19 — DB Integrity
- ✅ FKs on all relations; unique constraint on mudra slug; enum-backed statuses; soft-lifecycle via `status` (prescriptions are cancelled, never deleted).
- 🚧 UUID public IDs (see Rule 14).

## Rule 20 — API / AI-Service Security
- ✅ Laravel never exposes the AI services to the browser; they're server-to-server.
- 🔧 Both AI services now bind **127.0.0.1 only** (previously `0.0.0.0` = reachable on the LAN with a default key).
- ✅ AI endpoints require `X-API-Key`; error responses are structured, no stack traces (`APP_DEBUG` gate applies to Laravel).
- 🔧 Classifier body-size cap (5 MB).

## Rules 22–23 — Cache / Monitoring
- ✅ Cache keys are per-session (`practice:hold:{id}`) — no cross-user leakage; hold cache invalidated on completion.
- 🚧 Monitoring/alerting (failed logins, anomalies) — production item.

---

## Production Gate Summary (do before any real deployment)
1. `APP_DEBUG=false`, `APP_ENV=production`, HTTPS + HSTS live
2. Random `API_KEY` for AI services; keep 127.0.0.1 binding (or private network + TLS)
3. Session lifetime ≤ 30 min; consider 2FA (Fortify) + enforced email verification
4. CSP via Alpine CSP build + nonces
5. UUID route keys for patient-facing resources
6. Centralized logging + auth-failure alerting; encrypted backups
7. Re-run `composer audit` / `npm audit` in CI on every build
