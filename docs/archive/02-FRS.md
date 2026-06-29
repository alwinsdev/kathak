# 02 — Functional Requirements Specification (FRS)
**Project:** Siddha Mudra Therapy Platform
**Version:** 1.0 (Draft for approval) · **Date:** 2026-06-25

This document covers: business workflows (§3), frontend screens (§4), UI wireframes (§5), validation rules (§6). API contracts live in `03-TDD.md §6`; data model in `03-TDD.md §3`.

---

## 1. Actors & roles
- **Patient** — practices therapy, logs/confirms sessions, views own progress.
- **Doctor** — manages *their own* panel of patients, prescribes, reviews adherence, sends feedback.
- **Admin** — manages doctors, patients↔doctor assignment, mudra library, system settings, audit.

## 2. Permission matrix (RBAC)
| Capability | Patient | Doctor | Admin |
|---|:--:|:--:|:--:|
| Register self (patient) | ✅ | — | — |
| Manage own profile/password | ✅ | ✅ | ✅ |
| View own schedule/history | ✅ | — | — |
| Run practice / AI detect | ✅ | — | — |
| View/manage patients in **own panel** | — | ✅ | ✅(all) |
| Prescribe / deactivate mudra | — | ✅ (own panel) | ✅ |
| View adherence report | own | own panel | all |
| Send feedback message | reply | ✅ | ✅ |
| Create/disable doctor accounts | — | — | ✅ |
| Assign patient→doctor | — | — | ✅ |
| CRUD mudra library | — | — | ✅ |
| System settings / audit log | — | — | ✅ |

---

## 3. Business workflows (per feature)

### WF-1 Patient registration & consent
1. Visitor opens **Register** → fills name, email, password, demographics, condition.
2. System validates (§6), creates `users(role=patient)` + `patients`, hashes password.
3. **Consent screen** shown → patient accepts data-processing terms → `consent` recorded.
4. Patient is placed in **"Unassigned"** pool (no doctor yet) → sees onboarding state until a doctor is assigned.
5. Email verification link sent; unverified patients can log in but see a verify-banner.

### WF-2 Doctor provisioning (Admin)
1. Admin → **Users → Add Doctor** → enters name, email, specialty.
2. System creates `users(role=doctor)` with a random temp password + emails a **set-password** link.
3. Doctor follows link → sets password → logs in.

### WF-3 Assign patient to doctor (Admin)
1. Admin → **Assignments** → sees unassigned + all patients.
2. Selects patient → chooses doctor → confirms.
3. `patient_doctor` ownership row created/updated; audit-logged; patient & doctor notified.

### WF-4 Forgot / reset password
1. User → **Forgot password** → enters email.
2. System always responds "if the email exists, a link was sent" (no enumeration); if it exists, store a single-use, time-boxed token + email link.
3. User opens link → sets new password (strength-checked) → token consumed → all sessions invalidated.

### WF-5 Prescribe therapy (Doctor)
1. Doctor opens a patient in **own panel** → **Prescriptions**.
2. Adds a prescription: mudra, **frequency** (daily / specific weekdays / interval), **times-per-day** (one or many), **duration**, **reps/sets** (optional), **start date**, **end date** (optional), notes.
3. System validates, writes `assignments` + `assignment_schedules`; a **schedule snapshot** is created so adherence stays historically accurate (BR5).
4. Patient is notified; today's schedule updates.
5. Doctor can **deactivate** (soft) a prescription; end-date is stamped, history retained.

### WF-6 Daily practice + AI-verified completion (Patient)
1. Patient dashboard shows **today's due sessions** (derived from active schedules for today).
2. Patient taps **Practice** on a session → camera opens → live detection streams frames to inference proxy.
3. System tracks **target mudra match**: when the correct class is held ≥ *N* seconds above the confidence threshold, the session is marked **AI-verified**; an accuracy/confidence summary is stored as a `practice_session`.
4. Completion is recorded against that session/day with `source=ai_verified` (locked policy D3 — AI-verified only). **Manual "Mark Done" is removed from the normal patient flow.** If inference is unavailable, an **admin emergency override** (audit-logged, `source=manual_override`) can credit the session.
5. Reminders are **in-app only** for MVP (locked policy D4): server-side scheduler raises in-app notifications shown when the patient opens the app. (Email/Web-Push deferred.)

### WF-7 Review adherence & give feedback (Doctor)
1. Doctor opens patient → **Adherence**: time-accurate %, daily trend, per-mudra %, **AI-verified vs manual** split, streaks.
2. Non-adherent patients (< threshold) are flagged on the doctor dashboard.
3. Doctor sends a **feedback message**; patient sees it on dashboard and can reply.
4. Doctor can **export** the report (PDF/CSV).

### WF-8 Manage mudra library (Admin)
1. Admin → **Mudras** → list with search.
2. Create/edit: name, description, benefits, **category**, **reference image/video**, **AI class label** (maps Roboflow class → mudra), difficulty, status.
3. Soft-disable a mudra to retire it without breaking historical prescriptions.

### WF-9 Admin oversight
1. Admin dashboard: counts (patients, doctors, active prescriptions, today's adherence), recent audit events, system health.
2. System settings: confidence threshold, hold-seconds, reminder channels, data-retention window, completion policy (manual/AI/both).

---

## 4. Frontend screens (inventory)

> Existing screens are evolved, not thrown away. **New** = net-new.

**Public / Auth**
- S1 Landing (exists) · S2 Login (evolve: remove role guess, add forgot link) · S3 Register (evolve: + consent step) · S4 Forgot Password **(new)** · S5 Reset/Set Password **(new)** · S6 Email Verify **(new)**

**Patient**
- S7 Dashboard (evolve: due-today sessions, feedback inbox, verify banner) · S8 Practice (evolve: target-aware AI verification, hold timer) · S9 History (evolve: time-accurate, AI/manual split) · S10 Profile & Security **(new)** · S11 Consent **(new)**

**Doctor**
- S12 Dashboard (evolve: own-panel only, non-adherence flags) · S13 Patient detail / Prescriptions (evolve: rich scheduling) · S14 Adherence report (evolve: accurate + export + feedback) · S15 Profile & Security **(new)**

**Admin (all new)**
- S16 Admin Dashboard · S17 Doctors (list/create/disable) · S18 Patients & Doctor-assignment · S19 Mudra Library CRUD · S20 System Settings · S21 Audit Log

---

## 5. UI wireframes (low-fidelity)

```
S12 — DOCTOR DASHBOARD (own panel only)
┌───────────────────────────────────────────────────────────────┐
│ 🪔 Siddha Mudra Therapy           [Dashboard] [👤 Dr. A ▾] Logout│
├───────────────────────────────────────────────────────────────┤
│  My Patients                                                    │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────┐   │
│  │👥 Patients│ │📋 Active  │ │⚠ Flagged │ │📅 Today  25 Jun │   │
│  │    12     │ │ Rx   34   │ │  3 (<50%)│ │                  │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────────┘   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 🔎 Search patients…                       [+ New is admin]│  │
│  ├─────────────────────────────────────────────────────────┤   │
│  │ Patient        Cond.     Rx  Adher.  Last seen   Actions │   │
│  │ ● Ravi Kumar   arthritis  3   ▓▓▓▓░ 78%  2h ago  [Report][Manage]│
│  │ ⚠ Sita Devi    post-stroke 2  ▓░░░░ 22%  4d ago  [Report][Manage]│
│  └─────────────────────────────────────────────────────────┘   │
└───────────────────────────────────────────────────────────────┘

S13 — PRESCRIBE (rich scheduling)
┌───────────────── Prescribe Mudra Therapy — Ravi Kumar ─────────┐
│ Mudra      [ Pataka ▾ ]   (preview image + benefits panel →)   │
│ Frequency  (•) Daily  ( ) Weekdays [M T W T F S S]  ( ) Every N │
│ Times/day  [ 08:00 ] [ 18:00 ]  [+ add time]                   │
│ Duration   [ 10 ] min     Reps [ 3 ] × Sets [ 2 ] (optional)   │
│ Start [25-06-2026]  End [ optional ]                            │
│ Notes      [ start slowly, 5 reps … ]                          │
│                                   [ Cancel ]  [ Assign Mudra ] │
├────────────────────────────────────────────────────────────── │
│ Active Prescriptions      Mudra   Sched      Times   [Remove]  │
└───────────────────────────────────────────────────────────────┘

S8 — PATIENT PRACTICE (AI-verified)
┌──────────────────────────────── Live AI Practice — Pataka ─────┐
│ ┌───────────────────────────┐  Target: PATAKA                  │
│ │   [ live camera feed ]    │  Status: ● Live                  │
│ │   ┌─────────┐  bounding   │  Match:  Pataka 91% ✓            │
│ │   │ Pataka  │  box overlay│  Hold:   ▓▓▓▓▓▓▓░░ 4.2 / 5.0 s   │
│ │   └─────────┘             │  ┌─────────────────────────────┐ │
│ └───────────────────────────┘  │ ✅ Verified! Session logged │ │
│ Detection rate [1s ▾]  [⏸ Pause]│ └─────────────────────────────┘ │
│                                 [ Mark Done (manual) ]          │
└───────────────────────────────────────────────────────────────┘

S16 — ADMIN DASHBOARD
┌───────────────────────────────────────────────────────────────┐
│ Admin · Overview                                               │
│ [Patients 120] [Doctors 6] [Active Rx 410] [Today adher. 64%] │
│ ┌── Recent Audit ──────────────┐ ┌── System Health ─────────┐ │
│ │ 10:02 Dr.B prescribed Mushti │ │ Queue: ✅  Inference:✅   │ │
│ │ 09:55 Admin added Dr.C       │ │ Reminders sent today:312 │ │
│ └──────────────────────────────┘ └──────────────────────────┘ │
│ Quick: [Add Doctor] [Assign Patients] [Mudra Library] [Settings]│
└───────────────────────────────────────────────────────────────┘
```
*(Full-fidelity mockups produced per-module at implementation time.)*

---

## 6. Validation rules

### Global
- All POST requests require a valid **CSRF token**.
- All inputs server-side validated & sanitized; output HTML-escaped (already via `e()`).
- Reject payloads exceeding declared field limits; trim whitespace.

### Auth & profile
| Field | Rule |
|---|---|
| name | required, 2–100 chars, letters/spaces/`.`/`-` |
| email | required, RFC-valid, ≤150, unique per role-scope, normalized lowercase |
| password | required, ≥10 chars, must include 3 of {upper, lower, digit, symbol}, not in common-password list, ≤72 bytes (bcrypt) |
| role (login) | must be one of {patient, doctor, admin}; server authoritative |
| reset token | single-use, expires ≤30 min, constant-time compare |

### Patient profile
| Field | Rule |
|---|---|
| age | optional int 1–120 |
| gender | optional, enum {Male, Female, Other, Prefer-not} |
| phone | optional, E.164 or 7–15 digits |
| condition_notes | optional, ≤2000 chars |
| consent | required `true` before clinical use |

### Prescription
| Field | Rule |
|---|---|
| mudra_id | required, exists, status=active |
| frequency | required enum {daily, weekly, interval} |
| weekdays | required if weekly; subset of Mon–Sun, ≥1 |
| interval_days | required if interval; int 1–30 |
| times[] | ≥1 valid `HH:MM`, no duplicates, ≤6/day |
| duration_min | required int 1–120 |
| reps / sets | optional int 1–100 |
| start_date | required, ≥ today (or admin override) |
| end_date | optional, ≥ start_date |
| notes | ≤1000 chars |

### Practice / completion
| Field | Rule |
|---|---|
| image frame | ≤2 MB, JPEG/PNG only, dimensions capped; **authenticated patient only** |
| inference rate | server-enforced min interval (anti-abuse), e.g. ≥400 ms |
| AI verify | requires target class match ≥ confidence threshold held ≥ hold-seconds (admin-set) |
| completion source | enum {ai_verified, manual_override}; default path is `ai_verified`; `manual_override` is admin-only + audit-logged (policy D3); one completion per (session-slot, date) |

### Mudra (admin)
| Field | Rule |
|---|---|
| name | required, unique, 2–100 |
| ai_class_label | optional, unique if set (maps model class) |
| reference_media | optional, image ≤5 MB / video URL whitelist |
| category, difficulty | enum |
| status | enum {active, retired} |
