# 04 — Implementation Plan, Estimates & Sequencing
**Project:** Siddha Mudra Therapy Platform
**Version:** 1.0 (Draft for approval) · **Date:** 2026-06-25

Complexity = **L**ow / **M**edium / **H**igh. Time = ideal engineering days for **1 senior dev** (excludes review/QA buffer; multiply ~1.4 for calendar). Estimates assume the **Laravel** path (see TDD §2); add ~25–40% to infra/security tasks for harden-in-place.

---

## 1. Missing features — priority order

| Pri | ID | Feature | Why this priority |
|---|---|---|---|
| **P0** | F1 | Remove backdoors, secrets→env, secure inference proxy | Active exploitable holes (C1–C4) |
| **P0** | F2 | Security baseline: CSRF, session hardening, login throttle, security headers | Gate for exposing anything (C5–C7) |
| **P0** | F3 | Platform foundation: framework/MVC, env config, logging, audit, migrations, RBAC middleware | Enables every later module cleanly |
| **P1** | F4 | Admin role + admin dashboard | Unlocks doctor/mudra/assignment mgmt (C10) |
| **P1** | F5 | Identity: profiles, password change, forgot/reset, email verify | Core account lifecycle (WF-4) |
| **P1** | F6 | Doctor provisioning (admin-created doctors) | Onboarding without DB hacks (C10) |
| **P1** | F7 | Doctor–patient panel ownership + consent | Privacy correctness (C8, BR1/BR3) |
| **P1** | F8 | Mudra library CRUD + reference media + AI-class mapping | Clinical content + enables AI verify |
| **P2** | F9 | Flexible prescriptions (frequency, dates, multi-time, reps) | Real clinical scheduling (WF-5) |
| **P2** | F10 | Accurate adherence (snapshots, per-slot, AI/manual split) | Fixes C9; trustworthy reporting |
| **P2** | F11 | AI-verified completion (target match + hold-time + session log) | Makes AI clinically meaningful (C12) |
| **P2** | F12 | Server-side reminders (in-app/email/push, PWA) | Real adherence driver (C11) |
| **P3** | F13 | Doctor↔patient feedback messaging | Engagement loop (WF-7) |
| **P3** | F14 | Report exports (PDF/CSV) + non-adherence flags | Clinic efficiency (BO2) |
| **P3** | F15 | Admin analytics, settings, data-retention, audit viewer | Ops maturity |
| **P3** | F16 | Frontend refactor (extract CSS/components, a11y, PWA polish) | Maintainability/UX |
| **P3** | F17 | Test suite + CI/CD + deployment hardening | Production confidence |

## 2. Estimates & dependencies

| ID | Feature | Complexity | Est (days) | Depends on |
|---|---|:--:|:--:|---|
| F1 | Backdoors/secrets/proxy lockdown | L | 1–2 | — |
| F2 | Security baseline | M | 2–3 | F1 |
| F3 | Platform foundation (framework, RBAC, audit, logging) | H | 5–8 | F1 |
| F4 | Admin role + dashboard | M | 2–3 | F3 |
| F5 | Identity (profiles, reset, verify) | M | 3–5 | F3 |
| F6 | Doctor provisioning | L | 1–2 | F4, F5 |
| F7 | Panel ownership + consent | M | 3–4 | F4, F3 |
| F8 | Mudra library CRUD + media | M | 3–4 | F4 |
| F9 | Flexible prescriptions | H | 5–7 | F7, F8 |
| F10 | Accurate adherence + snapshots | H | 4–6 | F9 |
| F11 | AI-verified completion | H | 5–7 | F8, F9 |
| F12 | Server-side reminders (queue/PWA push) | H | 5–7 | F9, F3 |
| F13 | Feedback messaging | M | 2–3 | F7 |
| F14 | Exports + non-adherence flags | M | 2–3 | F10 |
| F15 | Admin analytics/settings/audit viewer | M | 3–4 | F3, F10 |
| F16 | Frontend refactor + a11y + PWA | M | 3–5 | F12 |
| F17 | Tests + CI/CD + deploy hardening | M | 4–6 (ongoing) | all |

**Indicative totals:** P0 ≈ 8–13 d · P1 ≈ 12–18 d · P2 ≈ 19–27 d · P3 ≈ 14–21 d → **~53–79 ideal dev-days** (≈ 11–16 calendar weeks for one senior dev incl. buffer; faster with 2–3 devs since several P1/P2 items parallelize once F3 lands).

## 3. Module delivery order (one module at a time — your approval gate)

Each module = self-contained, shippable, reviewed before the next.

```
M0  Security & Foundation         → F1, F2, F3         [must be first]
M1  Admin & Identity              → F4, F5, F6
M2  Care Relationship & Consent   → F7
M3  Mudra Library                 → F8
M4  Prescriptions & Scheduling    → F9
M5  Adherence Accuracy            → F10, F14
M6  AI-Verified Practice          → F11
M7  Reminders & Engagement        → F12, F13
M8  Admin Ops & Analytics         → F15
M9  Frontend/PWA Polish & Tests   → F16, F17 (F17 runs continuously from M0)
```
**Dependency-critical path:** M0 → M1 → M2 → M3 → M4 → (M5 ∥ M6) → M7. M8/M9 trail.

## 4. Per-module task breakdown (development order)

> Tasks listed in execution order. Detailed task lists for later modules are finalized at the start of each module (requirements can shift).

### M0 — Security & Foundation (F1–F3)
1. Add `.gitignore`; create `.env` + `.env.example`; move DB + Roboflow secrets out of source.
2. **Delete `fix_doctor.php`, `make_hash.php`**; add an admin-only seeder/console command for the initial admin.
3. Stand up framework skeleton (Laravel) or front-controller MVC; PSR-4; routing; base layout from existing Blade/CSS.
4. Port `users/patients/mudras/assignments/completions` to migrations (parity with `schema.sql`); seed mudras + initial admin.
5. Auth scaffolding: login/register/logout with hashing, **session regen**, secure cookie flags.
6. Middleware: `auth`, `role`, `csrf`, `throttle`; security headers; Monolog logging.
7. Secure inference proxy: auth + ownership + MIME/size caps + per-user rate-limit; key from env.
8. `audit_logs` + audit middleware/listener; `failed_logins` lockout.
9. Parity test: existing patient/doctor flows work on the new foundation.

### M1 — Admin & Identity (F4–F6)
1. Add `admin` role + `status`, `email_verified_at` to `users`.
2. Admin dashboard (counts, audit feed, health).
3. Profile + change-password (all roles).
4. Forgot/reset password (`password_resets`, enumeration-safe, single-use token).
5. Email verification (signed URL) + verify banner.
6. Doctor provisioning (admin creates doctor → set-password email).

### M2 — Care Relationship & Consent (F7)
1. `patient_doctor` table + `owns-patient` policy/middleware.
2. Refit doctor dashboard/assign/adherence to **own-panel only**.
3. Admin patient→doctor assignment screen.
4. Consent capture (`consents`) on registration + gate clinical processing.

### M3 — Mudra Library (F8)
1. Extend `mudras` (category, difficulty, `ai_class_label`, media, status).
2. Admin CRUD + media upload + search; soft-retire.
3. Map Roboflow classes → mudras (foundation for AI verify).

### M4 — Prescriptions & Scheduling (F9)
1. Extend `assignments`; add `assignment_schedules`.
2. Rich prescribe UI (frequency, weekdays/interval, multi-time, reps/sets, dates).
3. `PrescriptionService` + validation; soft-deactivate with end-date.
4. Derive patient "due today" from active schedules.

### M5 — Adherence Accuracy (F10, F14)
1. `schedule_snapshots` + nightly `SnapshotDailySchedules` job.
2. `AdherenceService` (per-slot, time-accurate, AI/manual split).
3. Rebuild doctor adherence + patient history on accurate data.
4. PDF/CSV export; nightly `FlagNonAdherence` + dashboard flags.

### M6 — AI-Verified Practice (F11)
1. `practice_sessions` table.
2. Practice page: target-aware matching, hold-timer, confidence display.
3. `PracticeVerificationService`: threshold + hold-seconds → verified completion (`source=ai_verified`) — the only normal completion path (policy D3).
4. Admin emergency override (`source=manual_override`, audit-logged) for inference outages; admin settings for threshold/hold.

### M7 — Reminders & Engagement (F12, F13)
1. `notifications` + per-minute scheduler `RaiseDueReminders` (writes in-app notification rows — policy D4).
2. Channel: **in-app only** for MVP (email/Web-Push deferred; queue worker already provisioned for later channels).
3. `messages` table + doctor↔patient feedback thread UI.

### M8 — Admin Ops & Analytics (F15)
1. `settings` (thresholds, channels, completion policy, retention).
2. Admin analytics (system adherence trends, usage).
3. Audit-log viewer; data-retention/export-delete tooling.

### M9 — Frontend/PWA Polish & Tests (F16, F17)
1. Extract CSS → stylesheet + component partials; a11y pass.
2. PWA manifest + service worker install/offline.
3. Fill test coverage; CI pipeline (lint, test, security scan); deployment hardening (HTTPS/HSTS, backups).

## 5. Recommended approach (item 16)
1. **Adopt Laravel** for the rebuild (TDD §2.1) — retires most security/infra work as built-ins.
2. **Strangler-fig migration**: keep the MVP live; replace module-by-module behind the same URLs; parity-test each before cutover.
3. **Security-first**: M0 ships before any new feature is exposed.
4. **Test continuously** (F17 from M0, not bolted on at the end).
5. **Approval gate per module** (your directive): I present module design + task list → you approve → I build → demo/parity → next.

---

## 6. Decision register (LOCKED — 2026-06-25)
| # | Decision | Choice | Consequence |
|---|---|---|---|
| D1 | **Stack** | **Laravel 11** (PHP 8.3), strangler-fig migration | Estimates above hold as-is (no +25–40% hardening tax) |
| D2 | **Compliance target** | **India DPDP baseline** | Consent + RBAC ownership + audit + encryption in transit/at-rest + export/delete; no HIPAA BAA controls in MVP |
| D3 | **Completion policy** | **AI-verified only** | A day's session counts only when the model confirms the target mudra (threshold + hold). See **risk R-AI** below — manual completion is removed as the *normal* path; an **admin-controlled, audit-logged emergency override** is retained for inference outages (NFR graceful-degradation). Reshapes M6 + FRS WF-6 + BR4. |
| D4 | **Reminder channels (MVP)** | **In-app only** | M7 scope shrinks (no email/push for reminders). **Caveat C-MAIL:** transactional email is still required by M1 for password-reset, doctor set-password, and email-verify — that is *not* a "reminder channel". Resolve M1 email at M1 kickoff (configure SMTP, or fall back to admin-set passwords / no email-verify). |
| D5 | **Team size / timeline** | *Still needed from you* | Drives conversion of ideal dev-days → a dated calendar plan |

**Risk R-AI (AI-verified-only):** if Roboflow is slow/down or model accuracy is low, patients cannot complete sessions → adherence and trust suffer. Mitigations baked into M6: show live confidence + hold progress, tunable threshold/hold (admin settings), and the audit-logged emergency manual override above. Recommend revisiting this policy after the first cohort's verified-rate is measured (BO3 target ≥ 60%).

> Per your instruction, **I will not generate production code until you approve a module.** On your go-ahead I'll start with **M0 (Security & Foundation)** and bring its detailed design + task list for sign-off.
