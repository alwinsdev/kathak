# 07 — POC Completion Plan
**Product:** Siddha Mudra Therapy — Proof of Concept
**Author:** Lead Product Architect / Full-Stack / UX / AI
**Date:** 2026-06-25
**Goal:** Turn the working POC into a complete, polished, demo-ready application — by *improving* the existing plain-PHP app, not redesigning it.

> Guardrails honored: no Laravel/Docker/Redis/queues/microservices. Reuse current architecture (procedural PHP pages + PDO + vanilla JS + Chart.js). Build incrementally. No code in this document; implementation waits for approval (§10).

---

## 1. Project Understanding

**What it is.** A two-role browser app for remote hand-rehabilitation: doctors prescribe Siddha mudras on a daily schedule; patients practice in front of the webcam while a trained Roboflow model (`kathak-trainer/8`) recognises the mudra live; the system records completion and shows adherence/streaks.

**Business objective.** Make daily home mudra-practice *visible, guided, and measurable* — closing the gap between the clinic and the patient's living room.

**User roles.** Doctor (prescribe + review), Patient (practice + track). No admin role (acceptable for POC; single seeded doctor + patient self-registration).

**Product workflow.** Doctor login → dashboard → select patient → prescribe → (patient) login → dashboard → practice → AI detect → complete → history; doctor reviews adherence.

**Screen flow.** `index → login/register → {doctor|patient}/dashboard`; doctor: `dashboard → assign → adherence`; patient: `dashboard → practice → history`.

**Database (5 tables).** `users`, `patients`, `mudras`, `assignments` (one daily `scheduled_time`), `completions` (`UNIQUE(assignment_id, completed_date)` → idempotent). Details: [docs/05 §7](05-POC-ASSESSMENT.md).

**AI workflow.** Browser captures JPEG frames every 0.5–2s → `predict.php` proxies to Roboflow → returns `class`/`confidence`/box → UI draws it. The recognised class is **not** compared to the prescribed mudra and **nothing is recorded** ([patient/practice.php:4,89-119](../patient/practice.php)).

---

## 2. Current Workflow (how it works today)

| Stage | File | Works? | Note |
|---|---|---|---|
| Login | `login.php` | ✅ | manual role pick |
| Doctor dashboard | `doctor/dashboard.php` | ✅ | lists all patients |
| Prescribe | `doctor/assign.php` | ✅ | insert + soft-remove |
| Adherence | `doctor/adherence.php` | ✅ | chart; numbers back-project |
| Patient dashboard | `patient/dashboard.php` | ✅ | schedule + manual mark-done |
| Reminder | `patient/dashboard.php` (JS) | ⚠️ | in-tab + clock-dependent |
| Practice | `patient/practice.php` | ✅ | live detection only |
| AI detect | `predict.php` | ✅ | open proxy |
| Completion | `patient/dashboard.php` | ⚠️ | manual only, not from AI |
| History | `patient/history.php` | ✅ | needs seed data; dead line at 22-23 |

---

## 3. POC Validation

| Objective | Status | Evidence |
|---|---|---|
| Business idea (prescribe→practice→track) | **Complete** | end-to-end pages exist and function |
| AI integration (live mudra recognition) | **Complete** | `predict.php` → Roboflow → boxes+confidence |
| Doctor workflow | **Complete** | login→prescribe→adherence all work |
| Patient workflow | **Partial** | practice + tracking work, but AI doesn't drive completion |
| End-to-end process (AI → recorded session) | **Partial** | the AI→completion hinge is missing |

**One-line verdict:** the POC *proves feasibility today*; closing the AI→completion hinge makes it *prove the actual value proposition*.

---

## 4. Missing Functionality (POC-completion only)

| ID | Missing item | Type | Priority |
|---|---|---|---|
| M1 | Detected mudra never compared to prescribed target; no "hold" confirm | AI logic | P0 |
| M2 | Successful detection doesn't record a completion | Workflow | P0 |
| M3 | Practice screen lacks Target / Match / Hold / Success feedback | UX feedback | P0 |
| M4 | Practice screen not linked to a specific assignment | Navigation/data | P0 |
| M5 | Public `fix_doctor.php` backdoor + `make_hash.php`; open `predict.php` | Demo safety | P1 |
| M6 | Reminder cannot be shown on demand | Demo functionality | P1 |
| M7 | Empty charts/streak (no seed history) | Demo realism | P1 |
| M8 | No loading/error states (camera, inference, forms) | User feedback | P1 |
| M9 | Adherence back-projection + dead line; manual role pick | Report/UX correctness | P2 |
| M10 | Doctor dashboard has no at-a-glance "who's behind" | Report signal | P2 |

Grouped into four buildable modules below.

---

## 5. Feature Design (no code)

### ⭐ Module A — AI-Verified Completion (covers M1–M4) — the POC's missing climax

- **Purpose:** Make a correct, AI-recognised practice *count* as the day's completion.
- **Business value:** Converts "the AI can see mudras" into "the AI verifies therapy was actually performed correctly" — the differentiator every investor/doctor will ask about.
- **User story:** *As a patient, when I hold my prescribed mudra in front of the camera and the AI confirms it, I want the session to be marked complete automatically, so I'm rewarded for practicing correctly instead of just clicking a button.*
- **Complete workflow:**
  1. Patient clicks "📷 Live AI" on a dashboard row → practice opens **for that assignment** (carries `assignment_id` + target mudra name).
  2. Camera streams frames (existing loop). For each response, compare the top prediction's `class` to the target mudra.
  3. If `class == target` and `confidence ≥ threshold` → advance a continuous **hold timer**; if not, reset it.
  4. When hold ≥ target seconds → POST a completion for that `assignment_id` tagged `source='ai_verified'` (+ best confidence).
  5. Show a clear "✅ Verified — session logged" state with a button back to the dashboard, where the row now shows "✓ Completed (AI-verified)".
- **Screen flow:** `patient/dashboard → patient/practice?assignment_id=…&mudra=… → (verified) → back to dashboard`.
- **Database changes (minimal, additive):** add `source VARCHAR(20) DEFAULT 'manual'` and `confidence DECIMAL(4,3) NULL` to `completions`. (No new tables. Existing unique key already prevents duplicate daily completion.)
- **Backend logic:** a small handler (extend the existing dashboard POST, or a tiny `complete.php`) that: validates the patient owns the active `assignment_id`, then `INSERT IGNORE INTO completions(assignment_id, completed_date, source, confidence)`. Keep the existing manual path intact as a fallback.
- **Frontend behaviour:** Target banner; live "Match: Pataka 91% ✓/✗"; a Hold progress bar (e.g. 0→3s); on success, success card + auto-stop camera; threshold + hold are simple constants (tweakable).
- **Validation rules:** confidence threshold (default ~0.5–0.7); hold duration (default ~3s continuous); `assignment_id` must be active and owned by the session patient; one completion per assignment/day (DB-enforced).
- **Acceptance criteria:**
  - Holding the **correct** mudra for the hold time records an AI-verified completion visible on dashboard + history.
  - Holding a **wrong** mudra never records a completion.
  - Re-verifying the same assignment the same day does not create a duplicate.
  - Manual "Mark Done" still works if used.
- **Edge cases:** model returns multiple/empty predictions (use highest-confidence / treat empty as no-match, reset hold); confidence flickers across the threshold (require *continuous* hold, reset on drop); camera denied/closed mid-hold (reset, show message); class-name mismatch between model and `mudras.name` (see Dependencies); network error on the completion POST (show retry, don't falsely show success).
- **Dependencies:** **the deployed model's class labels must match (or map to) the prescribed mudra names.** Confirm by reading one live Roboflow response first; if labels differ, add a tiny name-map (constant array) — no schema change needed.
- **Complexity:** Low–Medium. **Est. time:** 0.5–1.5 dev-days.

### Module B — Demo Safety (covers M5)
- **Purpose:** Make the app safe to run on any reachable URL during a demo.
- **Business value:** Avoids an embarrassing/abusable moment (public password reset, free use of the paid model).
- **User story:** *As a presenter, I want no hidden URLs that can reset accounts or burn the AI quota, so a live/shared demo can't be sabotaged.*
- **Workflow / backend:** delete `fix_doctor.php` and `make_hash.php`; in `predict.php` require an active patient session and reject non-image or oversized payloads.
- **DB / frontend:** none.
- **Validation rules:** request must have a logged-in patient session; MIME ∈ {jpeg,png}; size ≤ ~2 MB.
- **Acceptance criteria:** the backdoor URLs 404; an unauthenticated or oversized frame POST is rejected; normal practice still works.
- **Edge cases:** legitimate large frames (cap rate/quality already keeps frames small).
- **Dependencies:** none. **Complexity:** Low. **Est.:** 0.25–0.5 day.

### Module C — Demonstrability Pack (covers M6–M8)
- **Purpose:** Make the live walkthrough smooth and convincing.
- **Business value:** Removes the three things that make a working product *look* unfinished: clock-bound reminders, empty charts, and freeze-like latency.
- **User story:** *As a presenter, I want to trigger a reminder on demand, show populated history, and never see a frozen screen, so the demo feels real and polished.*
- **Scope & frontend behaviour:**
  - **Test reminder button** on the patient dashboard that fires the existing notification immediately.
  - **Seed script** creating a demo patient with ~3 weeks of completions (so streak/heatmap/adherence are populated).
  - **Loading/error states:** spinner on camera start + "Detecting…" while inference is in flight; disabled buttons on form submit; friendly camera-permission/denied copy.
- **DB changes:** none (seed is data, not schema).
- **Validation rules:** seed is idempotent/re-runnable.
- **Acceptance criteria:** reminder appears on click; charts/streak look full; no screen appears frozen during camera/inference.
- **Edge cases:** notification permission denied → fall back to in-page banner (already partially handled).
- **Dependencies:** none. **Complexity:** Low. **Est.:** 0.5–1 day.

### Module D — Reporting Credibility & Small UX (covers M9–M10)
- **Purpose:** Make the numbers and flows defensible under questioning.
- **Business value:** Adherence shown to a doctor/investor must be explainable; small frictions (manual role pick) shouldn't distract.
- **User story:** *As a doctor, I want the adherence figures to mean what they say, and to see at a glance who's behind.*
- **Scope:** label/repair the adherence window so percentages are honest; remove the dead line at [history.php:22-23](../patient/history.php); auto-detect login role from the account; (optional) inline adherence/flag on the doctor patient list.
- **DB changes:** none required (POC-level fix can label the window rather than add snapshots).
- **Validation rules:** n/a.
- **Acceptance criteria:** adherence % is explainable in one sentence; login needs no role selection; doctor list signals non-adherence.
- **Edge cases:** patient with no prescriptions (show "—" not 0%).
- **Dependencies:** none. **Complexity:** Low. **Est.:** 0.25–0.5 day.

**Total to "POC complete & demo-strong": ~1.5–3.5 dev-days.**

---

## 6. UI/UX Improvements (summary)
- **Practice screen** (biggest win): Target banner, live Match indicator, Hold progress bar, "✅ Verified" success state, camera spinner + inference status, friendly permission errors.
- **Patient dashboard:** AI-verified badge on completed rows; "Test reminder" button.
- **Doctor dashboard:** inline adherence / "behind" flag (optional, P2).
- **Login:** drop the manual role selector.
- **History/Adherence:** populated by seed data; honest window labels.
- All reuse the existing CSS design tokens in [includes/header.php](../includes/header.php) — no restyle.

---

## 7. AI Workflow Improvements
- **Target-aware matching:** compare top prediction `class` to the prescribed mudra (with an optional name-map constant).
- **Stability via hold-time:** require continuous match for N seconds to avoid single-frame false positives (also smooths confidence flicker).
- **Verification → record:** on success, write an `ai_verified` completion with the best confidence (turns the mirror into a referee).
- **Resilience:** treat empty/error responses as no-match (reset hold), show inference status, keep the manual fallback. (No change to the proxy mechanism beyond the safety gate in Module B.)

---

## 8. Demo Readiness Review (screen-by-screen)
Condensed (full simulation in [docs/06 Phase 7](06-PRODUCT-REVIEW.md)):
- **Landing → Doctor login → Prescribe:** ready; add a one-line condition tagline; acknowledge single-time scheduling as roadmap.
- **Patient dashboard:** add Test-reminder; show AI-verified badge.
- **Live AI Practice (climax):** today shows detection only → **after Module A** it shows hold-to-verify → logged. Keep a recorded clip as fallback for camera/model risk.
- **History + Adherence:** seed data so they're populated; label adherence honestly.
- **Top demo risks:** camera/HTTPS, Roboflow availability, empty data, the "is it really verified?" question — all mitigated by Modules A + C (+ B for shared URLs).

---

## 9. Development Roadmap (prioritized)

| Priority | Module | Why this priority |
|---|---|---|
| **P0** | **A — AI-Verified Completion** | It's the missing core of the POC and the climax of the demo; without it the value prop is unproven |
| **P1** | **B — Demo Safety** | Only critical if demoing on a reachable URL; trivial; prevents sabotage/cost abuse |
| **P1** | **C — Demonstrability Pack** | Makes a working product *look* finished (reminder/seed/loading) |
| **P2** | **D — Reporting Credibility & UX** | Strengthens believability under questioning; not blocking |

**Build order:** A → B → C → D. A is independent; B/C/D are independent of each other and can follow in any order (A first because it's the point of the POC).

---

## 10. Recommended Next Module

**Start with Module A — AI-Verified Completion.** It is the single change that converts this from "a demo of AI recognition" into "a demo of AI-verified therapy," directly closing the only Partial objectives in §3.

**Pre-flight (one quick check, no code):** read a single live response from `kathak-trainer/8` to confirm its class labels match the seeded mudra names ([schema.sql:67-77](../schema.sql)) — this determines whether a name-map is needed and de-risks the whole module.

---

### ✋ Approval gate
Per your instruction, **no code will be written until you approve.** On your go-ahead I will:
1. Run the one-line model-label pre-flight check (read-only), then
2. Implement **Module A** to the acceptance criteria above, then demo the closed AI→completion loop before proposing Module B.

**Approve Module A?**  ▢ Approve as-is  ▢ Approve with changes (note them)  ▢ Discuss / adjust priorities
