# 00 — Project Analysis & Gap Assessment
**Project:** Siddha Mudra Therapy Platform
**Author:** Lead Software Architect
**Date:** 2026-06-25
**Status:** Draft for approval

---

## 1. What the system is

A web platform where **doctors prescribe Siddha hasta-mudras (hand gestures) as rehabilitation therapy** and **patients practice them daily with live AI pose detection**. Target clinical use: post-stroke finger stiffness, arthritis, fine-motor rehabilitation.

**Stack today:** Procedural PHP 8 (no framework), MySQL 8 (PDO), vanilla JS + inline CSS, Roboflow serverless model for hand-gesture inference, Chart.js for charts. Runs on XAMPP/Apache.

## 2. Current capability inventory

| Area | Implemented | File(s) |
|---|---|---|
| Patient self-registration | ✅ | `register.php` |
| Login (role-select) / logout | ✅ | `login.php`, `logout.php` |
| Doctor: patient list + stats | ✅ | `doctor/dashboard.php` |
| Doctor: prescribe / remove mudra (1 daily time) | ✅ | `doctor/assign.php` |
| Doctor: 14-day adherence report + chart | ✅ | `doctor/adherence.php` |
| Patient: today's schedule, mark-done, suggestions | ✅ | `patient/dashboard.php` |
| Patient: live AI camera detection | ✅ | `patient/practice.php` |
| Patient: streak + 5-week heatmap + log | ✅ | `patient/history.php` |
| Roboflow inference proxy | ✅ | `predict.php` |
| Seeded mudra library (10 mudras) | ✅ | `schema.sql` |

**Data model:** `users`, `patients`, `mudras`, `assignments`, `completions`. (5 tables.)

## 3. Critical defects & risks (must address before production)

> These are not "features"; they are correctness/security blockers. They gate everything else.

| # | Severity | Issue | Evidence |
|---|---|---|---|
| C1 | 🔴 Critical | **Public password-reset backdoor.** Any unauthenticated visitor can reset the doctor's password. | `fix_doctor.php` |
| C2 | 🔴 Critical | **Debug hash dumper left in webroot.** | `make_hash.php` |
| C3 | 🔴 Critical | **`predict.php` is an open, unauthenticated proxy** to a paid Roboflow endpoint → cost abuse / API-key burn. No session check, no rate limit, no size/type validation. | `predict.php` |
| C4 | 🔴 Critical | **Secrets hardcoded in source** (DB creds blank-root, Roboflow API key). No `.env`, no `.gitignore`. | `config.php:8` |
| C5 | 🟠 High | **No CSRF protection** on any state-changing POST (register, login, assign, mark-done, remove). | all forms |
| C6 | 🟠 High | **No session hardening** — no `session_regenerate_id` on login, no `HttpOnly`/`Secure`/`SameSite` cookie flags. Session fixation possible. | `config.php:11` |
| C7 | 🟠 High | **No brute-force protection** on login (no throttle/lockout/captcha). | `login.php` |
| C8 | 🟠 High | **No doctor↔patient ownership.** Every doctor can view & manage **every** patient's records and prescriptions. Privacy violation for medical data. | `doctor/dashboard.php:5` |
| C9 | 🟡 Med | **Adherence math is historically inaccurate** — it back-projects *today's* active assignments across the whole 14/35-day window, so % is wrong whenever the prescription changed. | `doctor/adherence.php:37`, `patient/history.php:118` |
| C10 | 🟡 Med | **No way to create doctors** except direct DB insert or the backdoor. | — |
| C11 | 🟡 Med | **Reminders are client-only** — fire only while the dashboard tab is open; lost if browser closed. No server-side notification. | `patient/dashboard.php:137` |
| C12 | 🟡 Med | **AI is decorative** — detection result is never tied to completion; "Mark Done" is fully manual and unverifiable. | `practice.php` ↔ `dashboard.php` |
| C13 | 🟡 Med | No input validation depth (email format, password strength, phone), no server error logging, no audit trail. | global |
| C14 | 🟢 Low | Dead/duplicate code (`history.php:21-26` double-queries `totalActive`). | `patient/history.php` |

## 4. Functional gaps (the "missing features" — detailed & prioritized in `04-IMPLEMENTATION-PLAN.md`)

At a glance, the platform is missing: an **Admin role**, **doctor provisioning & profile management**, **password reset/forgot-password**, **doctor–patient panel ownership**, **mudra CRUD with reference media**, **richer scheduling** (frequency, date range, reps), **AI-verified completion**, **server-side reminders (email/push)**, **doctor↔patient messaging/feedback**, **report exports**, **consent/audit/privacy controls**, and **automated tests + CI/CD**.

## 5. Architectural recommendation (summary — full rationale in `03-TDD.md §2`)

**Recommendation: migrate to Laravel (PHP 8.3 LTS) before building new modules**, keeping MySQL and the Roboflow integration.

**Why:** The bulk of items C4–C7, C13 (env secrets, CSRF, auth middleware, validation, hashing, sessions, throttling, migrations, queues for reminders) are *free, batteries-included* in Laravel and would otherwise be hand-rolled and error-prone. Same language → low team-retraining cost. Eloquent ORM + migrations make the schema evolution (snapshots, soft-deletes, audit) clean. Queues/scheduler solve C11 reminders properly.

**Alternative considered:** Harden the existing procedural app in place (front-controller + small MVC structure, hand-rolled CSRF/middleware). Faster to *start*, but every later module re-pays the security/plumbing tax. Recommended **only** if a hard constraint forbids Composer/Laravel on the host.

> **Decision needed from you (does not block doc review):** Laravel migration vs. harden-in-place. The plans below are written so the *sequence* is identical either way; only the per-task effort differs. I flag the delta where it matters.

## 6. Assumptions (please correct any)

1. **Single-clinic / single-tenant** to start; multi-clinic is a future phase.
2. Medical data is in scope → we will design for **privacy & consent** (India DPDP Act 2023 baseline; HIPAA-style controls if US patients are ever targeted) but **full regulatory certification is out of MVP scope**.
3. Roboflow remains the inference provider; model accuracy/training is owned by the data-science track, not this roadmap.
4. Web-first (responsive + PWA); native mobile apps are a later phase.
5. English first, Hindi/regional i18n is a later phase.

---
*Next: see `01-BRD.md`, `02-FRS.md`, `03-TDD.md`, `04-IMPLEMENTATION-PLAN.md`.*
