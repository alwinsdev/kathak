# 05 — POC Assessment & Validation Report
**Project:** Siddha Mudra Therapy — Proof of Concept
**Reviewer:** Lead Technical Architect
**Date:** 2026-06-25
**Lens:** Proof-of-Concept feasibility validation (NOT enterprise evaluation)
**Method:** Full source-code read. Every conclusion cites code. No assumptions.

> Scope note: This report supersedes the enterprise recommendation in `00–04` *for the POC stage*. Plain PHP is retained; no Laravel/Redis/queue/microservice recommendations are made for POC completion. Framework discussion is confined to the long-term production phase (§20) only.

---

## 1. Executive Summary

The POC **substantially achieves its purpose**: it demonstrates an end-to-end loop where a doctor prescribes Siddha mudras, a patient receives and practices them, the camera streams frames to a trained Roboflow model that detects mudras in real time, and completion is recorded with adherence reporting and history.

There is **one material gap against the stated objective**: the **AI detection is not connected to completion**. The live model recognises mudras and draws bounding boxes ([patient/practice.php:99-118](../patient/practice.php)), but the practice screen never compares the detected class to the prescribed target and never records a completion. Completion is recorded only by a **manual "Mark Done" button** on the dashboard ([patient/dashboard.php:8-18](../patient/dashboard.php)). So the claim *"AI detects the mudra → the system records the session"* is **demonstrable in two separate halves but not as one closed loop**.

**Verdict:** The concept is **proven feasible and is demo-ready** for client/college/investor audiences, with the caveat that the AI→completion link is the headline feature most likely to be asked about and is currently not wired. Closing that single link (small effort) would lift this from "feasible" to "compelling."

**POC Readiness Score: 7.5 / 10** (see §15).

---

## 2. POC Objective

The POC sets out to prove six things are technically feasible:

| # | Objective | Source of truth |
|---|---|---|
| O1 | Doctors can prescribe Siddha mudras | `doctor/assign.php` |
| O2 | Patients can receive prescribed mudras | `patient/dashboard.php` |
| O3 | Patients can practice mudras | `patient/practice.php` |
| O4 | AI can detect mudras using the camera | `predict.php` + `patient/practice.php` |
| O5 | The system can record completion | `patient/dashboard.php` + `completions` table |
| O6 | The complete workflow is technically feasible | end-to-end |

Validation of each is in §8 (workflow) and the objective matrix in this report's POC-readiness section.

---

## 3. Business Problem

Siddha hasta-mudras (classical hand gestures) are used as a low-cost rehabilitation aid for fine-motor conditions (the seed data and copy reference *post-stroke finger stiffness, arthritis* — `register.php:52`, `index.php`). The clinical problems this addresses:

- **Home-exercise adherence is invisible.** Once a patient leaves the clinic, the clinician cannot see whether prescribed exercises are actually done.
- **Technique drifts without supervision.** Patients practicing alone have no feedback on whether they are forming the mudra correctly.
- **Manual tracking is unreliable.** Paper logs / memory give no measurable adherence.

The POC's bet: a browser app + a trained vision model can prescribe, guide, verify, and measure mudra practice remotely.

---

## 4. Proposed Solution

A two-role web application:

- **Doctor** prescribes mudras with a daily time and duration; reviews adherence.
- **Patient** sees today's schedule, practices in front of the webcam with live AI recognition, and the system tracks completion + streaks + history.
- **AI** (Roboflow serverless model `kathak-trainer/8`) performs real-time hand-gesture classification on camera frames.

Delivered as plain PHP server-rendered pages + vanilla JS for the camera/detection loop + Chart.js for charts + MySQL for data.

---

## 5. Project Architecture

**Style:** Classic procedural PHP, page-per-feature, server-rendered HTML with a shared header/footer. No framework, no router, no build step.

```
Browser (HTML + inline CSS + vanilla JS + Chart.js + WebRTC camera)
   │  form POST / GET (server-rendered pages)
   │  multipart frame POST (every 0.5–2s during practice)
   ▼
PHP pages (procedural)                     ── session auth (PHP $_SESSION)
   ├─ config.php  (PDO singleton, helpers, BASE_URL, session_start)
   ├─ auth: login / register / logout
   ├─ doctor/*   (dashboard, assign, adherence)
   ├─ patient/*  (dashboard, practice, history)
   └─ predict.php  ──curl──▶  Roboflow serverless model (kathak-trainer/8)
   ▼
MySQL 8 (PDO)  — users, patients, mudras, assignments, completions
```

**Cross-cutting helpers** (all in [config.php](../config.php)): `db()` (PDO singleton, lines 21-32), `current_user()` (34-36), `require_role()` (38-44), `e()` HTML-escape (46), `url()`/`BASE_URL` auto-detection (14-19). Auth is PHP session-based; pages call `require_role('doctor'|'patient')` at the top to gate access.

**Assessment (POC lens):** The architecture is appropriate and even *ideal* for a POC — minimal moving parts, instantly runnable on XAMPP, easy to demo. The page-per-feature procedural style is fine at this scale.

---

## 6. Folder Structure

```
kathak/
├── config.php            # DB + Roboflow config, session, shared helpers
├── schema.sql            # MySQL schema + seed (1 doctor, 10 mudras)
├── index.php             # Landing page
├── login.php             # Role-select login (patient/doctor)
├── register.php          # Patient self-registration
├── logout.php            # session_destroy + redirect
├── predict.php           # Roboflow inference proxy (UNAUTHENTICATED)
├── make_hash.php         # DEV: prints a bcrypt hash  ← debug leftover
├── fix_doctor.php        # DEV: resets doctor password ← backdoor leftover
├── README.md             # Setup + demo logins + flow
├── includes/
│   ├── header.php        # <head>, full inline CSS design system, nav
│   └── footer.php        # closing tags
├── doctor/
│   ├── dashboard.php     # All-patients list + stats
│   ├── assign.php        # Prescribe / soft-remove mudra
│   └── adherence.php     # 14-day adherence report + Chart.js
├── patient/
│   ├── dashboard.php     # Today's schedule, mark-done, suggestions, reminders
│   ├── practice.php      # Live camera + AI detection
│   └── history.php       # Streak + 5-week heatmap + recent log
└── docs/                 # Architecture docs (00–04) + this report (05)
```

**Observation:** A stray directory literally named `{includes,patient,doctor,assets}` exists at the repo root (an un-expanded shell brace pattern from a `mkdir`). It's empty/inert but should be deleted for cleanliness before any walkthrough.

**Two debug files must be removed before any *shared/public* demo** (see §17): [fix_doctor.php](../fix_doctor.php) (resets the doctor password to `doctor123` for *anyone* who opens the URL) and [make_hash.php](../make_hash.php) (prints a password hash). On a purely local/offline demo they are harmless; on any reachable URL they are a live risk.

---

## 7. Database Analysis

Schema: [schema.sql](../schema.sql). Five tables, clean and well-normalised for the POC.

| Table | Purpose | Notable design |
|---|---|---|
| `users` | Auth identity for both roles | `role ENUM('patient','doctor')`, unique `email`, bcrypt `password` |
| `patients` | Patient demographics (1:1 with users) | PK = `user_id`, `ON DELETE CASCADE`, `condition_notes` |
| `mudras` | Catalogue of mudras | `name`, `description`, `benefits`; seeded with 10 |
| `assignments` | A prescription (header) | `patient_id`, `mudra_id`, `doctor_id`, **single** `scheduled_time`, `duration_min`, `active` soft-flag |
| `completions` | A done-for-a-day record | `UNIQUE(assignment_id, completed_date)` → idempotent daily completion |

**Strengths (POC-appropriate):**
- Foreign keys are present and sensible; soft-deactivation via `assignments.active` preserves history ([assign.php:16-17](../doctor/assign.php)).
- The `UNIQUE(assignment_id, completed_date)` constraint is a genuinely good design choice — `INSERT IGNORE` makes "mark done" naturally idempotent ([patient/dashboard.php:14-15](../patient/dashboard.php)).
- Seed data (1 doctor + 10 real mudras with descriptions/benefits) makes the app demoable immediately after import.

**Limitations (acceptable for POC, listed for the record):**
- `assignments` supports only **one time per day** (`scheduled_time TIME`). No frequency/weekday/multi-time scheduling.
- `completions` has no `source` column, so there is nowhere to record *how* a session was completed (manual vs AI) — directly related to the AI→completion gap.
- No table linking a Roboflow class label to a `mudras` row, so AI verification has no mapping to lean on (matters only when O5 is closed).
- `assignments.patient_id` and `doctor_id` both FK to `users(id)`; there is no doctor↔patient ownership table (every doctor sees every patient). Fine for a single-doctor POC demo.

---

## 8. Workflow Analysis

The canonical flow: **Doctor login → Doctor dashboard → Patient selection → Mudra assignment → Patient dashboard → Reminder → Practice → AI detection → Completion → History.**

### 8.1 Doctor Login
- **Purpose:** Authenticate a clinician.
- **Implementation:** [login.php](../login.php). Role dropdown (patient/doctor), `SELECT … WHERE email=? AND role=?`, `password_verify`, sets `$_SESSION['user']`, redirects to `{role}/dashboard.php`.
- **Business logic:** Role is taken from the form *and* matched in the query, so a doctor must pick "Doctor". Demo creds `doctor@kathak.com / doctor123` (seeded).
- **DB:** reads `users`.
- **Missing for POC:** none blocking. (No CSRF/session-regen — out of POC scope per your security guidance, noted in §14.)
- **Improvement:** auto-detect role from the account instead of asking the user to pick it (minor UX).

### 8.2 Doctor Dashboard
- **Purpose:** Show the patient panel + headline stats.
- **Implementation:** [doctor/dashboard.php](../doctor/dashboard.php). Lists **all** patients with active-mudra counts; stat cards (total patients, active prescriptions, today's date); links to Report + Manage per patient.
- **DB:** `users JOIN patients`, correlated subquery for active assignment count (lines 5-10).
- **Missing:** no search/filter (fine for a small demo dataset). No per-patient adherence shown on the list (it lives one click away in the report).
- **Improvement (demo polish):** show a quick adherence % or "last practiced" per row so the dashboard tells a story at a glance.

### 8.3 Patient Selection
- **Purpose:** Pick a patient to manage.
- **Implementation:** "Manage" / "Report" links carry `?patient_id=` into `assign.php` / `adherence.php`.
- **DB:** target patient loaded by id ([assign.php:8-11](../doctor/assign.php)).
- **Missing:** no ownership check — any logged-in doctor can open any `patient_id`. Acceptable for single-doctor POC; flag for production.

### 8.4 Mudra Assignment
- **Purpose:** Prescribe a mudra with a daily time/duration/notes.
- **Implementation:** [doctor/assign.php](../doctor/assign.php). POST inserts into `assignments`; a `delete_id` POST soft-deactivates (`active=0`, scoped by `doctor_id`). Active list rendered below the form.
- **Business logic:** one mudra + one daily time per prescription; multiple prescriptions allowed.
- **DB:** writes `assignments`; reads `mudras`, `assignments JOIN mudras`.
- **Missing for POC:** none blocking. (Richer scheduling is a production concern, not a POC one.)
- **Improvement:** show the selected mudra's description/benefits/preview beside the form for a more convincing prescribe demo.

### 8.5 Patient Dashboard
- **Purpose:** Patient sees today's due mudras and marks them done.
- **Implementation:** [patient/dashboard.php](../patient/dashboard.php). Lists active assignments with a `done_today` subquery; stat cards (today's count, completed, pending, progress%); "📷 Live AI" link to practice; **manual "Mark Done"** form; "Suggested Mudras" (unassigned, limit 4).
- **Business logic:** progress = done/total for today; mark-done verifies ownership then `INSERT IGNORE completions`.
- **DB:** reads `assignments JOIN mudras` (+ completion subquery); writes `completions`.
- **Missing:** the only path to completion here is **manual** — there is no completion arriving from the AI practice screen.
- **Improvement:** surface whether each mudra was practiced/verified today.

### 8.6 Reminder
- **Purpose:** Nudge the patient at scheduled times.
- **Implementation:** **client-side only** ([patient/dashboard.php:137-161](../patient/dashboard.php)). JS polls every 30s; if the current `HH:MM` matches a row's `data-time` and the tab is open, it fires a browser `Notification` (or `alert`).
- **Business logic:** fires once per matched time per page load (`alerted` Set).
- **Missing:** fires **only while the dashboard tab is open**; nothing server-side. For a *live* demo this is hard to show convincingly (you'd have to wait for a wall-clock minute). 
- **Improvement (demo):** add a "Test reminder" button so the reminder can be demonstrated on demand instead of waiting for the clock.

### 8.7 Practice Session
- **Purpose:** Patient practices a mudra with the camera.
- **Implementation:** [patient/practice.php](../patient/practice.php). `getUserMedia` opens the camera; a loop captures frames to a hidden canvas and POSTs a JPEG to `predict.php` at the chosen rate; detection rate selector + pause/resume.
- **Business logic:** the prescribed mudra name arrives as `?mudra=` and is **displayed only** ([practice.php:4,10](../patient/practice.php)) — it is *not* used to verify the detected class.
- **DB:** none — the practice screen writes nothing.
- **Missing (KEY):** no target-vs-detected comparison, no "hold for N seconds", no completion write. This is where the AI→completion loop should close.
- **Improvement:** compare `p.class` to the target, show a hold timer, and on success record completion (see §11/§12).

### 8.8 AI Detection
- **Purpose:** Recognise the mudra in the frame.
- **Implementation:** [predict.php](../predict.php) base64-encodes the uploaded frame and `curl`s it to `ROBOFLOW_MODEL_URL` with the API key, echoing the JSON back. The client renders predictions: bounding boxes + class + confidence ([practice.php:89-119](../patient/practice.php)).
- **Business logic:** none server-side — it is a transparent proxy.
- **DB:** none.
- **Missing:** no auth/size/type guard (cost/abuse risk if public — §17); no normalisation of the Roboflow response shape.
- **Improvement:** gate the proxy behind the patient session and cap frame size; both are tiny changes that also de-risk the demo.

### 8.9 Completion
- **Purpose:** Record that a session was done.
- **Implementation:** [patient/dashboard.php:8-18](../patient/dashboard.php) — manual button only. `INSERT IGNORE INTO completions(assignment_id, completed_date, notes)`.
- **Business logic:** one completion per assignment per day (DB-enforced).
- **Missing:** completion is **decoupled from AI**. The system cannot currently say "this session was AI-verified."
- **Improvement:** allow the practice screen to create the completion when the AI confirms the target mudra.

### 8.10 History
- **Purpose:** Show consistency/progress to the patient (and a 14-day report to the doctor).
- **Implementation:** patient [history.php](../patient/history.php) — streak, total sessions, 5-week heatmap, recent 20 log; doctor [adherence.php](../doctor/adherence.php) — overall %, daily trend chart, per-mudra %.
- **Business logic:** adherence = completed ÷ expected over the window.
- **Missing / caveat:** both back-project *today's* active-assignment count across the whole window, so percentages distort if the prescription changed mid-window. Also a dead, buggy line in [history.php:22-23](../patient/history.php) (`(int)…->execute() ?: 0` evaluates to `1`, immediately overwritten by the correct query at 24-26) — harmless but should be removed.
- **Improvement (demo):** pre-seed a few weeks of `completions` so the charts and streak look populated during the walkthrough.

---

## 9. Feature Analysis

| Feature | Status | How it works | Backend | Frontend | DB | AI | Limitations |
|---|---|---|---|---|---|---|---|
| Patient registration | ✅ Implemented | Form → transactional insert → auto-login | `register.php` | `register.php` form | `users`,`patients` | — | Leaks raw DB error on non-duplicate failure (`register.php:27`) |
| Login / logout | ✅ Implemented | Role-select, `password_verify`, session | `login.php`,`logout.php` | `login.php` | `users` | — | Manual role pick; no CSRF (out of POC scope) |
| Doctor dashboard | ✅ Implemented | List all patients + stats | `doctor/dashboard.php` | same | `users`,`patients`,`assignments` | — | No search; no ownership scope |
| Prescribe mudra | ✅ Implemented | Insert assignment; soft-remove | `doctor/assign.php` | same | `assignments`,`mudras` | — | Single daily time only |
| Adherence report | ✅ Implemented | 14-day chart + per-mudra % | `doctor/adherence.php` | Chart.js | `completions`,`assignments` | — | Back-projection inaccuracy (C9) |
| Patient dashboard | ✅ Implemented | Today's schedule + mark-done | `patient/dashboard.php` | same | `assignments`,`completions` | — | Completion is manual only |
| Reminders | ⚠️ Partial | Client-side `setInterval` notification | — (JS only) | `patient/dashboard.php` | — | — | Tab-open only; nothing server-side |
| Live practice | ✅ Implemented | Camera → frame POST loop | `predict.php` | `patient/practice.php` | — | Roboflow | Writes nothing |
| AI detection | ✅ Implemented | Proxy → Roboflow → boxes+confidence | `predict.php` | `patient/practice.php` | — | Roboflow `kathak-trainer/8` | Open proxy; response not normalised |
| **AI-verified completion** | ❌ Missing | — | — | — | — | — | **Detection never recorded as completion** |
| History / streak | ✅ Implemented | Heatmap, streak, recent log | `patient/history.php` | same | `completions`,`assignments` | — | Inaccurate streak math; dead line |
| Suggested mudras | ✅ Implemented | Unassigned mudras, limit 4 | `patient/dashboard.php` | same | `mudras`,`assignments` | — | Static suggestion (not personalised) |

---

## 10. AI Integration Analysis

- **Provider:** Roboflow serverless inference, model `kathak-trainer/8` ([config.php:9](../config.php)). API key hardcoded ([config.php:8](../config.php)).
- **Path:** browser captures a JPEG frame → `POST ../predict.php` (multipart) → server base64-encodes → `curl` to Roboflow with `?api_key=` → JSON returned → client renders `predictions[]` with `class`, `confidence`, and `x/y/width/height` bounding boxes ([practice.php:99-118](../patient/practice.php)).
- **Cadence:** every 2000 / 1000 / 500 ms, user-selectable ([practice.php:25-29,69](../patient/practice.php)); a `busy` flag prevents overlapping requests.
- **What works:** This proves the core technical claim — **a trained model can recognise Siddha mudras from live webcam frames in the browser**, with confidence and localisation, in near-real-time. That is the hardest and most impressive part of the POC and it is functional (subject to the deployed model's accuracy).
- **What's missing for the objective:** the detected `class` is never compared to the prescribed mudra, there is no "held correctly for N seconds" logic, and no completion is written. So AI is currently a **live demo of recognition**, not a **verification of practice**.
- **Robustness gaps (demo-relevant):** the proxy is unauthenticated and unbounded ([predict.php:5-9](../predict.php)); a network/model hiccup surfaces only as a status-text error; the Roboflow response shape is assumed, not validated.

---

## 11. Gap Analysis (POC-completion only — not enterprise)

Only gaps that matter for *completing/strengthening the POC* are listed. Enterprise concerns are deliberately excluded.

| ID | Gap | Why needed (POC) | Business value | Priority | Complexity | Impl. order |
|---|---|---|---|---|---|---|
| G1 | **Connect AI detection → completion** | It is the headline objective (O5/O6); without it the "AI verifies practice" story is unproven | High — this *is* the differentiator | 🔴 P0 | Low–Med | 1 |
| G2 | **Target-vs-detected matching + hold timer** | Makes verification meaningful (not "any mudra counts") | High | 🔴 P0 | Low | 2 (with G1) |
| G3 | **Remove/secure demo backdoors** (`fix_doctor.php`, `make_hash.php`) before any reachable demo | Prevents an embarrassing/abusable URL during a public demo | Med | 🟠 P1 | Low | 3 |
| G4 | **Gate + cap the inference proxy** (session check, size/type limit) | Stops API-key/cost abuse if the demo URL is shared | Med | 🟠 P1 | Low | 4 |
| G5 | **On-demand reminder demo** ("Test reminder" button) | Reminders can't be shown live without waiting for the clock | Med (demo UX) | 🟠 P1 | Low | 5 |
| G6 | **Seed demo data** (a patient + a few weeks of completions) | Empty charts/streaks undersell the product in a walkthrough | Med | 🟢 P2 | Low | 6 |
| G7 | **Basic loading/error states** on practice + forms | Camera/model latency currently looks like a freeze | Med | 🟢 P2 | Low | 7 |
| G8 | **Fix adherence back-projection + dead line** | Numbers shown to a doctor/investor should be defensible | Low–Med | 🟢 P2 | Low | 8 |
| G9 | **Auto-detect login role** | Removes a confusing manual step | Low | 🟢 P3 | Low | 9 |

> Explicitly **out of POC scope** (production, not now): doctor↔patient ownership, consent/audit, password reset/email verify, rich scheduling, server-side reminders, CSRF/session hardening, framework migration. These belong to §20.

---

## 12. Missing Features (design — no code)

### MF-1 — AI-Verified Completion (closes G1 + G2) — the one that matters
- **Purpose:** Turn live recognition into a recorded, verified session.
- **Business requirement:** When a patient holds the *prescribed* mudra above a confidence threshold for a few continuous seconds, the session for that assignment/day is marked complete and tagged as AI-verified.
- **Workflow:** Practice screen receives the target mudra (already passed as `?mudra=` and `assignment_id` can be added) → on each detection, compare `prediction.class` to target → if matched ≥ threshold, advance a hold timer → at the hold goal, POST a completion → show "✅ Verified, session logged" → offer return to dashboard.
- **Database changes:** add `source` (e.g. `ai_verified` / `manual`) and optional `confidence` to `completions`; optionally a small map of Roboflow class → `mudras.id` (or match on name). Minimal and additive.
- **Backend logic:** a small endpoint (or extend the existing mark-done handler) that accepts `assignment_id` + verification metadata, validates ownership, and `INSERT IGNORE` into `completions` with `source='ai_verified'`.
- **Frontend screens:** evolve `practice.php` — add Target banner, live Match%, Hold progress bar, success state; pass `assignment_id` from the dashboard's "Live AI" link.
- **Validation rules:** confidence ≥ threshold (e.g. 0.5–0.7, tweakable); hold ≥ N seconds (e.g. 3–5); one completion per assignment/day (already enforced by the unique key).
- **Dependencies:** the deployed Roboflow model must emit class names that match (or can be mapped to) the prescribed mudra names.
- **Complexity:** **Low–Medium.** **Est. time:** ~0.5–1.5 dev-days.
- **Acceptance criteria:** holding the correct mudra for the hold-time records a completion visible on the dashboard/history as AI-verified; holding a *wrong* mudra does not.

### MF-2 — Demo Hardening of the Inference Proxy (G3 + G4)
- **Purpose:** Make the demo safe to expose without enterprise overhead.
- **Workflow / backend:** require an active patient session in `predict.php`; reject non-image or oversized payloads; delete `fix_doctor.php` and `make_hash.php`.
- **DB:** none. **Frontend:** none. **Validation:** MIME ∈ {jpeg,png}, size ≤ ~2 MB.
- **Complexity:** **Low.** **Est. time:** ~0.25–0.5 dev-day.
- **Acceptance criteria:** unauthenticated frame POST is rejected; backdoor URLs no longer exist.

### MF-3 — Demonstrability Pack (G5 + G6 + G7)
- **Purpose:** Make the live walkthrough smooth and convincing.
- **Scope:** "Test reminder" button; a seeded demo patient with ~3 weeks of completions; spinners/disabled-state on camera start, frame send, and form submits; friendly camera-permission error copy.
- **DB:** a seed script (additive). **Frontend:** small JS/UX additions.
- **Complexity:** **Low.** **Est. time:** ~0.5–1 dev-day.
- **Acceptance criteria:** reminder shows on click; charts/streaks look populated; no screen ever appears frozen.

### MF-4 — Reporting Credibility (G8 + G9)
- **Purpose:** Numbers and flows that hold up to questions.
- **Scope:** fix adherence to count only days a prescription was active (or, simplest POC fix, label the window honestly + remove the dead line in `history.php`); auto-detect role at login.
- **Complexity:** **Low.** **Est. time:** ~0.25–0.5 dev-day.
- **Acceptance criteria:** adherence % is explainable; login needs no role pick.

**Total to "POC complete & demo-strong": ~2–4 ideal dev-days.**

---

## 13. UI/UX Review

Per-screen, demo-oriented.

| Screen | Strengths | Issues (demo-relevant) | Suggested fix |
|---|---|---|---|
| Landing (`index.php`) | Clean, clear value prop, two CTAs | — | none needed |
| Login | Simple, demo creds shown | Manual role selection; no "show password" | Auto-detect role (G9) |
| Register | Good field set, inline validation hints | Raw DB error can surface (`register.php:27`) | Friendly message |
| Patient dashboard | Strong stat cards, clear schedule, suggestions | Reminder not demoable live; no AI-verified indicator | "Test reminder" (G5); verified badge |
| Practice | Impressive live boxes + confidence | No target match, no hold feedback, no success state, latency looks like a freeze | MF-1 UI + loading state (G7) |
| History | Great heatmap + streak | Looks empty without data | Seed data (G6) |
| Doctor dashboard | Useful stats + clear table | No at-a-glance adherence | optional inline % |
| Assign | Functional, lists active Rx | No mudra preview/benefits beside form | optional preview panel |
| Adherence | Polished Chart.js report | Numbers can mislead (back-projection) | label window / fix (G8) |

**General UX gaps:** missing loading indicators (camera init, frame inference, form submit); errors are mostly plain text; navigation is good (consistent nav + "back" links). Nothing here blocks a demo except the practice-screen feedback (covered by MF-1) and empty-data optics (G6).

---

## 14. Technical Review

- **Project structure:** Appropriate for a POC — readable, page-per-feature, shared header/footer, one config. No structural problems at this scale.
- **Code quality:** Consistent, parameterised PDO queries throughout (no SQL injection), output escaped via `e()`. One dead/buggy line ([history.php:22-23](../patient/history.php)). Some duplicated inline styles (cosmetic).
- **Database:** Solid for the scope (§7); the idempotent completion key is a highlight. Main functional limit is the missing `source` column for AI completion.
- **Performance:** Fine for a demo. The only load source is the practice frame loop (0.5–2s) hitting Roboflow; the `busy` guard prevents pile-ups. No N+1 concerns at demo scale.
- **Maintainability:** Good enough for a POC. If it grows, the inline-CSS-in-header and copy-pasted markup would warrant extraction — *not* a POC concern.
- **Security (major demo-blockers only, per your guidance):**
  - 🔴 **`fix_doctor.php`** — public password-reset backdoor. Remove before any reachable demo. (Harmless offline.)
  - 🟠 **`make_hash.php`** — prints a hash. Remove.
  - 🟠 **`predict.php`** — unauthenticated, unbounded proxy to a paid model. Gate + size-cap before sharing a URL (cost/abuse).
  - 🟡 **Secrets in source** (`config.php`) — fine for a local demo; rotate the Roboflow key if the repo is ever shared.
  - Everything else (CSRF, session hardening, throttling, ownership) is **intentionally deferred** — not needed to demonstrate the concept.

---

## 15. POC Readiness Score

**7.5 / 10.**

| Dimension | Score | Rationale |
|---|---|---|
| Core concept proven | 9/10 | Prescribe→receive→practice→detect all work end-to-end |
| Objective completeness | 6/10 | AI→completion loop not wired (O5/O6 only half-closed) |
| Demo smoothness | 6/10 | Empty-data optics, no loading states, reminder not live-demoable |
| Stability/risk | 7/10 | Depends on live camera + Roboflow + HTTPS/localhost |
| Safety for a shared demo | 6/10 | Backdoor + open proxy if exposed (trivial to fix) |

**Closing MF-1 alone moves this to ~9/10** because it converts the most-questioned claim into a working demonstration.

---

## 16. Demonstration Readiness

| Audience | Ready now? | Notes |
|---|---|---|
| **Client** | ✅ Yes (local) | Full flow demoable; lead with the live camera detection; pre-seed data |
| **Doctor** | ✅ Yes | Prescribe + adherence resonate; expect "does it know if they did it right?" → that's MF-1 |
| **College / academic** | ✅ Yes | Strong project: real ML integration + full-stack + clinical framing |
| **Investor** | ⚠️ Yes, with MF-1 | Investors will probe the AI-verification claim; wire G1/G2 first for credibility |

**Recommended demo script:** seed a patient with history → log in as doctor → prescribe a mudra → log in as patient → show schedule → open Live AI → form the mudra, show real-time detection + confidence → (after MF-1) hold to verify → completion appears → show history/streak → show doctor adherence report.

---

## 17. Risks During Demo

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Camera blocked (no HTTPS / permission denied) | Med | High | Demo on `localhost` or HTTPS; pre-grant permission; have a recorded clip as backup |
| Roboflow down/slow/rate-limited | Low–Med | High | Test beforehand; lower detection rate; offline screen-recording fallback |
| Model misclassifies live | Med | Med | Practice the exact pose/lighting; choose mudras the model handles best |
| Empty charts/streak look unfinished | Med | Med | Seed demo data (G6) |
| Someone hits `fix_doctor.php` / open proxy on a shared URL | Low | High | Remove backdoors + gate proxy (MF-2) before exposing |
| "Mark Done is manual — where's the AI?" question | High | High | Wire MF-1 so completion comes from detection |
| Reminder can't be shown (clock-dependent) | High | Low | "Test reminder" button (G5) |

---

## 18. Recommended Improvements (priority order, POC-scoped)

1. **MF-1 — AI-verified completion** (target match + hold timer + record). *The* improvement.
2. **MF-2 — Remove backdoors + gate the proxy** (safe to share).
3. **MF-3 — Demonstrability pack** (test-reminder, seed data, loading/error states).
4. **MF-4 — Reporting credibility** (adherence honesty + dead-line removal + auto role).

All four ≈ **2–4 ideal dev-days**, plain PHP, no new infrastructure.

---

## 19. Development Roadmap (POC → Production)

> Per your instruction, this does **not** redesign the POC and introduces production technology only where it becomes genuinely necessary, and only in the later phases.

**Phase 1 — POC Completion (now, ~2–4 days, stay on plain PHP)**
Close the AI→completion loop (MF-1), make it demo-safe and demo-smooth (MF-2/3), tidy reporting (MF-4). Outcome: the stated objective is fully demonstrated.

**Phase 2 — Pilot (small real use, weeks)**
Add the minimum to put it in front of *one* real doctor and a handful of patients: password reset, basic input validation/error handling everywhere, doctor↔patient scoping (so a doctor sees only their patients), persisted practice sessions (store confidence/attempts), and basic consent text. Still plain PHP; introduce `.env` for secrets. No queues/Redis.

**Phase 3 — MVP (broader cohort, months)**
Richer scheduling (frequency/multiple times), accurate time-aware adherence (per-day expected snapshots), server-side reminders (a simple cron is sufficient — still no message broker), exports for clinicians, audit logging, and a proper roles/admin area for onboarding doctors. This is the point at which a light framework *may* pay off; evaluate then, don't pre-commit.

**Phase 4 — Production (scale + compliance)**
Only here consider a framework (for built-in auth/CSRF/validation), India DPDP compliance (consent/audit/retention/encryption), automated tests + CI/CD, observability, and backups. Enterprise infrastructure (queues, caching tiers, etc.) is introduced **only if real load data justifies it** — not by default.

---

## 20. Next Phase Planning

**Immediate next step:** Phase 1 (POC completion), implemented as four small modules in this order, each behind your approval gate:

| Order | Module | Closes | Complexity | Est. |
|---|---|---|---|---|
| 1 | AI-Verified Completion (MF-1) | G1, G2 | Low–Med | 0.5–1.5 d |
| 2 | Demo Hardening (MF-2) | G3, G4 | Low | 0.25–0.5 d |
| 3 | Demonstrability Pack (MF-3) | G5, G6, G7 | Low | 0.5–1 d |
| 4 | Reporting Credibility (MF-4) | G8, G9 | Low | 0.25–0.5 d |

**Dependencies:** MF-1 depends on the Roboflow model emitting mudra class names that match (or can be mapped to) the prescribed mudra names — this should be confirmed first by reading one live response from the deployed model. The rest are independent.

**Acceptance for "POC complete":** the full demo script in §16 runs start-to-finish, the camera-detected mudra produces a recorded completion, and there are no backdoor/empty-data/freeze moments in the walkthrough.

> No code will be written until you approve a module. On your go-ahead I will start with **Module 1 (AI-Verified Completion)** and bring its detailed, source-grounded build plan for sign-off first.
