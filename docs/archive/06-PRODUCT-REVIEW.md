# 06 — Product Review Report
**Product:** Siddha Mudra Therapy — Proof of Concept
**Reviewer:** Product Architect · Healthcare Domain · Business Analyst · UX · AI Consultant
**Date:** 2026-06-25
**Lens:** Product-first POC evaluation (pre-demonstration). Not a code review. Not an enterprise redesign.

> Purpose: help us (1) complete the POC, (2) improve the demonstration, (3) prepare for stakeholder presentations, (4) plan future development.

---

## Phase 1 — Understand the Business

**Business problem.** Hand/finger rehabilitation (post-stroke stiffness, arthritis, fine-motor loss) depends on *daily home practice that nobody can see*. The clinician loses visibility the moment the patient leaves the room. Three failures follow: patients forget or do exercises wrong, clinicians can't measure adherence, and follow-up visits are spent re-checking instead of progressing.

**Current solution (the status quo this replaces).** A therapist demonstrates mudras in-clinic; the patient is told to "do these at home." Tracking is verbal/paper, technique is unverified, and the next appointment relies on the patient's self-report. There is no objective signal of *whether* or *how well* practice happened.

**Proposed solution.** A web app that turns Siddha mudra therapy into a guided, measurable, AI-assisted loop: doctors prescribe specific mudras with a daily schedule; patients practice in front of their webcam while a trained model recognises the mudra in real time; the system records completion and shows adherence/streaks to both sides.

**Product vision.** "A remote rehabilitation coach for the hands" — classical Siddha mudra knowledge delivered as a personalised, self-correcting home-therapy program, with the clinician kept in the loop through objective adherence data.

**POC objective.** Prove the loop is technically and experientially real: **prescribe → receive → practice → AI-detect → record → review** can happen in a browser, end to end.

**Success criteria (for the POC, not production):**
- A doctor can prescribe a mudra in under a minute.
- A patient can open the camera and see the AI name their mudra live.
- A completed practice becomes a visible record.
- A stakeholder watching the demo *believes the core idea works* and asks "when can real patients use it?" rather than "does the AI actually do anything?"

---

## Phase 2 — Understand the Users

### Doctor / Therapist
- **Goals:** prescribe the right mudras quickly; know who is actually practicing; spend clinic time on progress, not policing.
- **Responsibilities:** choose mudras + timing for each patient; monitor adherence; intervene when a patient falls behind.
- **Expectations:** fast prescribing, trustworthy adherence numbers, a clear "who needs attention" signal.
- **Pain points (today):** zero home visibility; unreliable self-reports; manual tracking.
- **Current experience in the POC:** Logs in → sees a patient list with active-prescription counts → opens a patient → assigns a mudra with a daily time/duration → views a 14-day adherence chart. **This journey is complete and convincing.** Gap they'll feel: the dashboard doesn't tell them *at a glance* who's behind (they must open each report), and adherence can't yet distinguish "marked done" from "AI-verified."

### Patient
- **Goals:** know exactly what to do today; do it correctly; feel progress.
- **Responsibilities:** practice on schedule; confirm completion.
- **Expectations:** clear schedule, simple practice, instant feedback that they're doing it right, a sense of streak/achievement.
- **Pain points (today):** forgetting; uncertainty about correct form; no encouragement.
- **Current experience in the POC:** Registers → sees today's schedule + suggestions → opens "Live AI" → camera recognises the mudra with a confidence box → returns and presses "Mark Done" → sees streak/heatmap. **Mostly complete.** The felt gap: the AI *shows* recognition but doesn't *reward* it — completion is a separate manual button, so the patient doesn't experience "the app saw me do it correctly, and it counted."

### Administrator
- **Status in POC:** **not present as a role.** There is a single seeded doctor and patient self-registration; doctor accounts are created by DB seed / the (to-be-removed) helper script. For a POC this absence is acceptable — a single clinic/doctor is enough to prove the idea. Worth naming so stakeholders aren't surprised.

---

## Phase 3 — Understand the Product (end-to-end journeys)

### Doctor Journey
1. **Login** (role-select) → 2. **Dashboard** (all patients, counts, today's date) → 3. **Select patient** ("Manage") → 4. **Prescribe** (pick mudra, set daily time + duration + notes; soft-remove existing) → 5. **Review adherence** (overall %, daily trend chart, per-mudra %). *Coherent and demo-ready.*

### Patient Journey
1. **Register / Login** → 2. **Dashboard** (today's prescriptions, progress stats, suggestions, in-tab reminders) → 3. **Practice** ("Live AI" opens camera, live detection) → 4. **Complete** (manual "Mark Done") → 5. **History** (streak, 5-week heatmap, recent log). *Coherent, with one broken hinge between steps 3 and 4 — see AI journey.*

### AI Journey
1. Patient opens Practice with a target mudra named on screen → 2. Browser captures webcam frames every 0.5–2s → 3. Frames are sent to the Roboflow model → 4. The model returns the recognised mudra class + confidence + location → 5. The UI draws a labelled bounding box and confidence. **Then the journey stops.** The recognised class is never checked against the prescribed target, and nothing is recorded. The AI is a *mirror*, not yet a *referee*.

### System Journey
Registration creates the patient record; prescribing creates an assignment; "Mark Done" writes one idempotent completion per day; dashboards/reports read completions to compute progress, streaks, and adherence. The data backbone is sound; the missing write is "a completion that originates from a successful AI detection."

---

## Phase 4 — Feature Review

| Feature | Purpose | Business value | Implementation | UX | Strengths | Weaknesses | Missing improvement |
|---|---|---|---|---|---|---|---|
| Patient registration | Onboard patients | Self-serve growth | Form + auto-login | Smooth, good fields | Captures condition/demographics | Can show a raw DB error on failure | Friendlier error |
| Login / logout | Access control | Trust | Role-select + verify | Simple | Demo creds shown | Manual role pick is odd | Auto-detect role |
| Doctor dashboard | See patients | Clinical oversight | List + stats | Clean | Active-Rx counts | No at-a-glance adherence / "who's behind" | Inline % + flag |
| Prescribe mudra | Assign therapy | Core clinical act | Insert/soft-remove | Fast | Notes + duration + soft delete | One daily time only; no mudra preview | Preview panel |
| Adherence report | Measure adherence | Closes the loop for doctors | Chart.js + per-mudra | Polished | Visual, immediate | Numbers back-project current Rx | Honest window labelling |
| Patient dashboard | Daily plan | Drives behaviour | Schedule + mark-done | Strong stat cards | Suggestions add discovery | Reminder only while tab open; no AI-verified state | Verified badge + test reminder |
| Live practice + AI | Guide + verify | **The differentiator** | Camera loop → model | Impressive | Real-time boxes + confidence | Doesn't match target or record | Connect to completion |
| History / streak | Motivation | Retention | Heatmap + streak | Delightful | Looks great with data | Empty without seeded data; streak math approximate | Seed demo data |
| Suggested mudras | Discovery | Engagement | Unassigned list | Nice touch | Encourages conversation w/ doctor | Static, not personalised | (later) personalise |

**Headline:** every feature needed to *tell the story* exists; the single feature that would make the story *land* (AI-driven completion) is the one not yet wired.

---

## Phase 5 — POC Validation

**Does the POC prove the business idea?** **Yes, with one caveat.** It demonstrates the full prescribe→practice→track loop and — critically — a *working trained model that recognises Siddha mudras from a live webcam*. That recognition capability is the hard, credible, differentiating part, and it works.

**Can it be demonstrated?** Yes, on `localhost`/HTTPS with a camera. The flow is short and legible.

**Would doctors understand it?** Immediately — it mirrors prescribe-and-review. Their instinctive question: *"Does it tell me if they did it correctly, not just that they clicked done?"* Today the honest answer is "the AI can see it, but completion is still a manual click." That's the gap to close.

**Would patients understand it?** Yes — schedule, camera, done, streak is intuitive. The missed delight is that the AI doesn't yet *reward* correct practice.

**Would investors believe in it?** They'll believe the AI is real (live detection sells itself). They'll **probe the verification claim** — "what stops a patient from clicking done without practicing?" Wiring AI→completion converts that objection into a wow moment.

**Would researchers understand the innovation?** Yes — applying a trained vision model to classical Siddha mudra recognition for tele-rehabilitation is a clear, defensible novelty.

**Would clients see business value?** Yes — remote adherence visibility and reduced follow-up load are concrete. Strengthen with one populated adherence report in the demo.

**Conclusion:** The POC is **valid and demonstrable today**, and becomes **compelling** once the AI detection visibly produces the completion record.

---

## Phase 6 — Gap Analysis (POC-completion only)

Only what's needed to *complete and sell the POC*. Enterprise items excluded.

- **Missing AI logic (P0):** target-vs-detected matching + a short "hold the pose" confirmation; this is the missing hinge.
- **Missing workflow (P0):** a successful detection should *record* the session (AI-verified completion) — close the loop between Practice and History/Adherence.
- **Missing UX feedback (P1):** on the practice screen — a Target banner, live Match indicator, a Hold progress bar, and a clear "✅ Verified — session logged" success state.
- **Missing demo functionality (P1):** a "Test reminder" button (reminders currently depend on the wall clock + an open tab); pre-seeded demo data so charts/streaks aren't empty.
- **Missing states (P1):** loading indicators (camera starting, inference in flight, form submitting) and friendly camera-permission/error messages — so latency never looks like a freeze.
- **Missing dashboard signal (P2):** an at-a-glance "who's behind" flag / inline adherence on the doctor list.
- **Missing report honesty (P2):** label/repair the adherence window so the numbers are explainable.
- **Missing small UX (P3):** auto-detect login role; mudra preview beside the prescribe form.

(No missing screens are *blocking*; the practice screen needs enrichment, not a new screen.)

---

## Phase 7 — Demo Simulation (presenter walkthrough)

**Screen 0 — Landing**
- *Presenter:* "This is a remote rehab coach for the hands — doctors prescribe Siddha mudras, patients practice with live AI guidance."
- *Audience sees:* clean value prop, two clear CTAs.
- *Likely questions:* "What are Siddha mudras?" "Who's it for?"
- *Weakness:* none. *Improvement:* one-line "for post-stroke / arthritis / fine-motor rehab" under the headline.

**Screen 1 — Doctor logs in → Dashboard**
- *Presenter:* "Here's the clinician's view — their patients and how many active prescriptions each has."
- *Audience sees:* stat cards + patient table.
- *Questions:* "Can a doctor see who's falling behind?"
- *Weakness:* adherence isn't on the list. *Improvement:* inline % / flag (P2).

**Screen 2 — Prescribe a mudra**
- *Presenter:* "I pick a mudra, set the daily time and duration, add a note — prescribed."
- *Audience sees:* fast form + active prescriptions list.
- *Questions:* "Can you schedule multiple times a day / specific days?"
- *Weakness:* single daily time. *Improvement:* acknowledge as roadmap (don't over-build for POC).

**Screen 3 — Patient logs in → Dashboard**
- *Presenter:* "The patient sees today's plan and their progress."
- *Audience sees:* schedule, stats, suggestions.
- *Questions:* "Do they get reminders?"
- *Weakness:* reminder is clock-dependent/in-tab. *Improvement:* "Test reminder" button to show it on demand (P1).

**Screen 4 — Live AI Practice (the moment)**
- *Presenter:* "They open the camera and the model recognises the mudra in real time — here's the live confidence."
- *Audience sees:* webcam feed + labelled bounding box + confidence %.
- *Questions:* **"Does it know if they did it *correctly*? What stops them faking completion?"**
- *Weakness (today):* detection isn't tied to completion. *Improvement (P0):* with AI-verified completion wired, you hold the correct mudra, a progress bar fills, "✅ Verified — logged" appears. **This is the climax of the demo — make it land.**

**Screen 5 — Completion → History**
- *Presenter:* "That session is now recorded — here's the streak and the 5-week heatmap."
- *Audience sees:* populated streak + heatmap + recent log.
- *Questions:* "Does the doctor see this?"
- *Weakness:* empty if not seeded. *Improvement:* seed data (P1).

**Screen 6 — Doctor adherence report**
- *Presenter:* "And the clinician sees objective adherence — daily trend and per-mudra."
- *Audience sees:* Chart.js report.
- *Questions:* "How is adherence calculated?"
- *Weakness:* back-projection. *Improvement:* honest window labelling (P2).

**Strongest moment:** Screen 4 once AI→completion is wired. **Riskiest moment:** Screen 4 if the camera/model misbehaves — have a recorded clip as a fallback.

---

## Phase 8 — Future Vision (Pilot → MVP → Production)

**Pilot (one real doctor, a few patients).** Add only what real use demands: password reset, validation/error polish everywhere, doctor-sees-only-their-patients scoping, persisted practice attempts (store confidence), and a short consent notice. Stay on plain PHP; move secrets to a config file. *No enterprise infrastructure.*

**MVP (a real cohort).** Richer scheduling (multiple times/specific days), time-accurate adherence, reliable reminders (a simple cron suffices), clinician report export, and a lightweight admin area to onboard doctors. Re-evaluate tooling *here*, based on evidence — don't pre-commit.

**Production (scale + trust).** Compliance (India DPDP: consent, audit, retention, encryption), automated tests + CI/CD, observability, and backups. Heavier infrastructure only if real load justifies it.

> The product's center of gravity is the **AI-verified practice loop**. Every phase should deepen that loop (accuracy, feedback richness, clinical insight) before broadening around it.

---

## Summary Scorecard

| Question | Answer |
|---|---|
| Solves the problem? | Yes — remote, measurable, AI-guided mudra therapy |
| Workflow makes sense? | Yes — clean doctor + patient journeys |
| Users will understand it? | Yes — both roles intuitive |
| Anything confusing? | Manual role pick; empty data; latency-as-freeze |
| Biggest weakness | AI detection not connected to completion |
| Demo-ready today? | Yes (local); compelling once the loop is closed |
| Effort to "POC complete & demo-strong" | ~2–4 dev-days, plain PHP |

**Recommended next step:** wire **AI-verified completion** (the climax of the demo), then add the demo-experience polish (test-reminder, seed data, loading/error states). No architecture change required.
