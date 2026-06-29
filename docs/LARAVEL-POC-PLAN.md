# Siddha Mudra Therapy — Laravel POC Design
**Status:** Design for approval — **no code until approved** · **Date:** 2026-06-29
**Type:** Proof of Concept (NOT production). Core goal: **prove AI can verify a prescribed mudra and auto-complete the session.**

> Supersedes the earlier enterprise docs (`docs/00–04`, `docs/modules/M0`) — those were over-scoped. This is the authoritative plan.

---

## 1. Scope (locked by owner)
**Two roles only: Doctor, Patient. No admin. No manual "Mark Done" — AI verification is the only completion path.**

**In scope:** doctor prescribes mudras + schedule; patient practices with camera; Roboflow detects; system compares to the prescribed mudra; correct mudra held for the required duration → **auto-completed**; doctor sees adherence/progress.

**Out of scope (do not build):** admin portal, hospital mgmt, billing, payments, multi-clinic, email/SMS, complex scheduling, enterprise security, microservices, queues. Laravel's *built-in* auth/CSRF/hashing are kept (they're free defaults, not "enterprise extras").

## 2. The core AI flow (the thing this POC proves)
```
Doctor prescribes mudra (e.g. "Pataka")  →  stored with its Roboflow class label
        ↓
Patient opens Practice for that prescription  →  camera starts
        ↓
Browser sends frames → /practice/{prescription}/detect → Roboflow → predictions
        ↓
Server compares top prediction.class vs prescribed mudra's ai_class_label,
        confidence ≥ threshold
        ↓
Correct mudra held continuously ≥ required hold seconds
        ↓
POST /practice/{session}/verify  →  practice_session marked verified
        ↓
Completion auto-recorded for today  →  patient & doctor dashboards update
```

## 3. Technology
- **Laravel 11**, PHP 8.2+, MySQL 8.
- **Auth:** Laravel **Breeze** (Blade stack) — minimal, official, gives login/register/CSRF/hashing out of the box. Extended with a `role` column.
- **Frontend:** Blade + a little **Alpine.js** + vanilla JS for camera/WebRTC and the detection loop (ported in spirit from the current `practice.php`). Chart.js for adherence charts.
- **Roboflow:** key + model URL in `.env`, called server-side via a dedicated `RoboflowClient` service (replaces the old open `predict.php`).
- Secrets in `.env`; `.gitignore` excludes `.env`/`vendor`. (Basic hygiene, not enterprise hardening.)

## 4. Project structure (clean but POC-sized)
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/...                       (Breeze)
│   │   ├── Doctor/{DashboardController, PrescriptionController, AdherenceController}.php
│   │   └── Patient/{DashboardController, PracticeController, HistoryController}.php
│   ├── Middleware/EnsureRole.php          (role:doctor | role:patient)
│   └── Requests/{StorePrescriptionRequest, RegisterPatientRequest}.php
├── Models/{User, PatientProfile, Mudra, Prescription, PracticeSession, Completion}.php
├── Services/
│   ├── Roboflow/RoboflowClient.php        (integration; key from config)
│   ├── Practice/MudraVerificationService.php  (compare + hold logic, records completion)
│   └── Adherence/AdherenceService.php     (adherence %, streak, trend)
└── Policies/PrescriptionPolicy.php        (doctor owns patient / patient owns session)

resources/views/{layouts, auth, doctor, patient}/...
routes/web.php
database/migrations/...   database/seeders/{DoctorSeeder, MudraSeeder}.php
config/services.php  (roboflow block)
```
Controllers stay thin → Services hold the logic → Eloquent models. Validation in Form Requests. Authorization in Policies. That's the "clean architecture" footprint appropriate for a POC — no over-engineered layering.

## 5. Database design (6 tables)

| Table | Columns (key ones) | Notes |
|---|---|---|
| **users** | id, name, email (unique), password, **role** enum(`doctor`,`patient`), timestamps | role decided by record, not by login form (fixes the old "pick your role at login" flaw) |
| **patient_profiles** | id, user_id→users, **doctor_id→users** (assigned doctor), age, gender, phone, condition_notes, timestamps | links patient to their doctor (see Q1) |
| **mudras** | id, name, slug, description, benefits, **ai_class_label** (maps Roboflow class), reference_image_path?, active, timestamps | seeded reference library; `ai_class_label` is what AI compares against |
| **prescriptions** | id, patient_id→users, doctor_id→users, mudra_id→mudras, **scheduled_time** (TIME), **duration_min**, **hold_seconds** (verify hold, default 5), **confidence_threshold** (default 0.75), notes, active, timestamps | one row per prescribed mudra (supports "one or more") |
| **practice_sessions** | id, prescription_id, patient_id, target_class, started_at, completed_at?, **verified** (bool), best_confidence, hold_achieved_seconds, frames_evaluated, timestamps | one AI practice attempt; the evidence record |
| **completions** | id, prescription_id, practice_session_id, **completed_date**, timestamps, **unique(prescription_id, completed_date)** | auto-written on verification; drives adherence |

Adherence = completions ÷ active prescriptions over a date window (per mudra and overall). Streak = consecutive fully-completed days.

**Relationships:** User hasOne PatientProfile; User(patient) hasMany Prescriptions; Prescription belongsTo Mudra; Prescription hasMany PracticeSessions; PracticeSession hasOne Completion.

## 6. Screens
**Auth:** Login (role from record), Register (patient + profile + pick doctor).
**Doctor:** Dashboard (my patients + adherence at a glance) · Patient detail / Prescribe (add/remove mudra, set time, duration, hold seconds, notes) · Adherence report (trend chart + per-mudra %).
**Patient:** Dashboard (today's prescribed mudras + status) · **AI Practice** (camera, live detection overlay, target mudra, hold progress bar, auto "Verified!") · History (heatmap/streak + recent sessions).

## 7. API / routes (core)
```
# Doctor (middleware role:doctor)
GET    /doctor/dashboard
GET    /doctor/patients/{patient}
POST   /doctor/patients/{patient}/prescriptions
DELETE /doctor/prescriptions/{prescription}
GET    /doctor/patients/{patient}/adherence

# Patient (middleware role:patient)
GET    /patient/dashboard
GET    /patient/practice/{prescription}
POST   /patient/practice/{prescription}/detect    # frame → Roboflow → predictions (auth, owns prescription, size/type checked)
POST   /patient/practice/{session}/verify         # finalize: mark verified + record completion
GET    /patient/history
```
`detect` returns `{ predictions:[{class,confidence,x,y,width,height}], match:bool, confidence }`. `verify` is server-authoritative: it confirms a verified session exists and writes the completion (idempotent per day).

## 8. Validation (essentials)
- Register: name 2–100; email valid+unique; password ≥8 (Laravel default); age 1–120; gender enum; phone optional; doctor_id exists & is a doctor.
- Prescription: mudra exists+active; scheduled_time `HH:MM`; duration_min 1–120; hold_seconds 2–60; confidence_threshold 0.1–0.95; notes ≤1000.
- detect: authenticated patient who owns the prescription; image JPEG/PNG ≤2 MB.

## 9. Implementation plan (modules, in build order)
Each module is approved before the next (your gate). POC-sized estimates (ideal dev-days).

| # | Module | What ships | Complexity | Est. |
|---|---|---|:--:|:--:|
| **L1** | Foundation & Auth | Laravel + Breeze install, `role` + `EnsureRole`, base layout (port current look), migrations for all 6 tables, `DoctorSeeder` + `MudraSeeder`, `.env`/Roboflow config | M | 1.5–2.5 d |
| **L2** | Doctor — Prescribing | Doctor dashboard (my patients), patient detail, prescribe/remove mudra + schedule, `PrescriptionService` + Form Request + Policy | M | 1.5–2.5 d |
| **L3** | Patient — Schedule & History | Patient dashboard (today's mudras + status), history (streak/heatmap/recent), `AdherenceService` | M | 1.5–2.5 d |
| **L4** | AI Practice & Verification ⭐ | `RoboflowClient`, practice screen (camera + detection loop + hold timer + target compare), `MudraVerificationService`, auto-completion, `detect`/`verify` endpoints | **H** | 3–4 d |
| **L5** | Adherence polish & demo | Doctor adherence charts, patient progress, seed demo data, end-to-end demo run, README/runbook | L–M | 1–2 d |

**Total ≈ 8.5–13.5 ideal dev-days.** Critical path is strictly L1→L2→L3→L4; L4 is the heart of the POC. (L2 and L3 can overlap once L1 lands.)

## 10. Design decisions (confirmed by owner 2026-06-29)
1. **Patient ↔ doctor link:** patient **picks their doctor from a dropdown at registration**; doctors are pre-seeded. No admin needed.
2. **Verification hold:** **~5 seconds** (`hold_seconds`, default 5), separate from the prescribed practice `duration_min`.
3. **Confidence threshold:** **default 0.75** (stricter), tunable per prescription.
4. **Auth scaffold:** Laravel **Breeze (Blade)**.

---
**Next step:** on your go-ahead I start **L1 (Foundation & Auth)** and bring it for review before L2.
