# Module L3 — Patient Module — Design (for approval)
**Status:** Design — no code until approved · **Date:** 2026-06-29
**Builds on:** L1 (auth/roles), L2 (prescriptions). **Followed by:** L4 (AI Practice & Verification).

> Reminder (locked spec): **completion is AI-verified only — no manual "Mark Done".** So L3 *shows* the schedule and history; the act of completing a session happens through AI in **L4**. L3 builds everything around that, with the camera/verification screen stubbed as a placeholder (the way L1 stubbed dashboards).

---

## 1. Business workflow
```
Patient logs in → Patient Dashboard
   → "Today's Mudras": active prescriptions due today (status=active, start_date ≤ today)
        each shows time, mudra, duration, notes, completion status, [Practice] button
   → clicks a mudra → Prescription Detail (description, benefits, schedule, notes)
   → clicks [Practice] → Practice entry point  → (L4: camera + AI verification)
   → "History": past practice sessions + simple stats (totals, this week, streak)
```
The patient never edits clinical data. Their only "write" is performing a practice session, which is recorded by AI verification in L4.

## 2. Screens

### 2.1 Patient dashboard (replaces L1 placeholder)
- Stat cards: **Today's Mudras**, **Completed Today**, **Pending**.
- **Today's prescriptions** list: time · mudra · duration · notes · status badge (Done/Pending) · **[Practice]**.
- Empty state when nothing is prescribed/due.

### 2.2 Today's prescribed mudras (the core list)
"Due today" = prescription `status = active` AND `start_date ≤ today`. (No end_date in the POC.) Ordered by `scheduled_time`. "Completed today" = a verified practice session exists for that prescription dated today (data arrives in L4; shows Pending until then).

### 2.3 Prescription details
Dedicated read-only page: mudra name, description, benefits, scheduled time, duration, start date, doctor's notes, and a [Practice] button. Patient may only view **their own** prescriptions (else 403).

### 2.4 Practice entry point
A **[Practice]** button → `patient.practice.show` route. In L3 this resolves to a **placeholder page** ("AI practice — arriving in L4") so navigation works and is testable. L4 replaces the placeholder with the live camera + Roboflow verification screen. (Mirrors how L1 shipped placeholder dashboards.)

### 2.5 Practice history
Read-only page: list of past practice sessions (date, mudra, verified/confidence, duration) + simple stats (total sessions, sessions this week, current streak). Renders an empty state until L4 starts recording sessions.

## 3. Navigation flow
```
Landing/Login ──► Patient Dashboard ──► Prescription Detail
                        │   └────────────► Practice (entry → L4 screen)
                        └────────────────► History
Nav bar (patient): Dashboard · History · profile menu (from L1)
```

## 4. Database impact — ONE decision needed

L3's history needs a place to read completed/verified sessions from. Two clean options:

- **Option A (recommended): L3 introduces the `practice_sessions` table now** (the read model) and L4 fills it during AI verification.
  - Columns: `id`, `prescription_id` (FK), `patient_id` (FK), `practiced_on` (date), `started_at`, `completed_at` (nullable), `status` (enum: `in_progress`/`verified`/`abandoned`), `best_confidence` (decimal, nullable), `detected_class` (nullable), `timestamps`. Indexes: `(patient_id, practiced_on)`, `(prescription_id)`.
  - Pros: history is a *real* L3 deliverable and testable now; L4 gets a stable target to write to. L4 may add columns **additively** later if needed.
- **Option B: L3 has no DB impact.** History page is built but shows an empty state; the `practice_sessions` table and populated history land in L4.
  - Pros: strict "writer owns the table" boundary. Cons: history isn't truly functional/testable until L4.

**My recommendation: Option A** — it makes "Practice history" a genuine L3 feature and de-risks L4. The rest of this design assumes Option A; tell me if you prefer B and I'll adjust (history becomes a placeholder).

> A new `App\Enums\PracticeStatus` enum accompanies Option A.

## 5. Routes (under existing `patient.` group, `role:patient`)
| Method | URI | Name | Purpose |
|---|---|---|---|
| GET | `/patient/dashboard` | `patient.dashboard` | today's mudras (replaces placeholder) |
| GET | `/patient/prescriptions/{prescription}` | `patient.prescriptions.show` | prescription detail (own only) |
| GET | `/patient/practice/{prescription}` | `patient.practice.show` | practice entry (placeholder; real in L4) |
| GET | `/patient/history` | `patient.history` | practice history + stats |

Route-model binding + a `view-own-prescription` gate (patient owns the prescription) → 403 otherwise.

## 6. Controllers (thin)
- `Patient\DashboardController@index` — today's due prescriptions + counts (via service).
- `Patient\PrescriptionController@show` — one owned prescription's detail.
- `Patient\PracticeController@show` — placeholder practice page (L4 replaces).
- `Patient\HistoryController@index` — history list + stats (via service).

## 7. Form Requests
**None.** L3 is **read-only** for the patient — there are no input forms (completion is AI-only in L4). Authorization is handled by middleware + an ownership gate, not Form Requests. (Listed for completeness; intentionally empty for this module.)

## 8. Services
- `PatientScheduleService` — `dueToday(User $patient): Collection` and today's counts (active + start_date ≤ today, with today's completion status).
- `PracticeHistoryService` — `recent(User $patient)`, plus stats (total sessions, sessions this week, current streak). Reads `practice_sessions`.

All query/business logic lives here; controllers stay thin.

## 9. Validation rules
No form validation in L3 (no writes). **Authorization rules** instead:
- Patient may view only their **own** prescriptions, practice entry, and history (`patient_id === auth id`) → else 403.
- Doctor role → 403 on all patient routes (L1 guard, regression-tested).

## 10. Testing strategy
Feature tests:
- **Dashboard**: shows today's active prescriptions for the patient; hides cancelled and future-`start_date` ones; doesn't show other patients' prescriptions.
- **Prescription detail**: patient views own (200); another patient's → 403.
- **Practice entry**: own prescription reachable (200 placeholder); another's → 403.
- **History**: renders; lists seeded practice sessions; ownership enforced; empty state when none.
- **Role isolation**: doctor → 403 on patient routes.

Unit tests:
- `PatientScheduleService` due-today logic (status/start_date boundaries, completion flag).
- `PracticeHistoryService` stats (totals, this-week, streak).

(With Option A, a `PracticeSession` factory is added to seed history in tests.)

## 11. Out of scope for L3 (lands in L4)
Camera/WebRTC, Roboflow inference proxy, target-match + hold-timer verification, **writing** practice sessions / marking completion. L3 delivers the read/navigation shell those plug into.

## Estimate
Medium · ~1.5–2.5 dev-days.

---
**Decisions confirmed by owner (2026-06-29)**
1. **Database impact: Option A** — L3 introduces the `practice_sessions` table (read model); L4 populates it via AI verification.
2. **Practice entry point: placeholder page in L3** — real camera/AI screen arrives in L4.

**Awaiting explicit approval to implement.**
