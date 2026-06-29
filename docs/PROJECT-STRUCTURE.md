# Project Structure & Architecture Summary
**Project:** Siddha Mudra Therapy (Laravel POC)
**As of:** End of L1 — Foundation & Auth · **Date:** 2026-06-29
**Checkpoint:** commit `61fc6b9`, tag `v0.1.0-l1`

---

## 🔒 L1 freeze

L1 is **frozen**. From this point on, **no structural changes** to the foundation
(directory layout, base auth, role model, foundation migrations, config layout)
**unless they are bug fixes**. New capability is added by new modules (L2+), not by
reshaping L1. Any unavoidable structural change must be raised and approved
explicitly before it is made.

---

## High-level architecture
Layered, framework-standard Laravel with a thin domain layer:

```
Browser (Blade + Tailwind + Alpine, Vite assets)
        │  HTTP
        ▼
Routes ──► Middleware (auth, verified, role) ──► Controllers (thin)
                                                    │
                                 FormRequest (validate)
                                                    │
                                 Action / Service (business logic)
                                                    │
                                 Eloquent Models ──► MySQL (siddha_mudra)

Server-side integrations (e.g. Roboflow in L4) live behind Services,
keys read from config/.env — never exposed to the browser.
```

## Directory map (what lives where)
```
kathak/
├── app/
│   ├── Actions/                     # single use-case business operations
│   │   └── RegisterPatient.php      # create patient user + profile (transactional)
│   ├── Enums/
│   │   ├── Role.php                 # doctor | patient
│   │   └── Gender.php               # male | female | other
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/...             # Breeze auth (login, register, password, verify)
│   │   │   ├── Doctor/DashboardController.php    # role-scoped controllers
│   │   │   └── Patient/DashboardController.php
│   │   ├── Middleware/
│   │   │   └── EnsureRole.php       # 'role:doctor' / 'role:patient' gate
│   │   └── Requests/
│   │       └── Auth/RegisterPatientRequest.php  # validation lives here
│   ├── Models/
│   │   ├── User.php                 # role cast, isDoctor/isPatient, scopes, relations
│   │   ├── PatientProfile.php       # demographics + doctor link
│   │   └── Mudra.php                # reference library, slug route key, scopeActive
│   ├── Providers/AppServiceProvider.php
│   └── View/Components/             # AppLayout, GuestLayout (Breeze)
├── config/
│   ├── practice.php                 # confidence_threshold, hold_seconds, etc. (from .env)
│   └── services.php                 # roboflow { key, model_url } (from .env)
├── database/
│   ├── factories/                   # User (+doctor state), PatientProfile, Mudra
│   ├── migrations/                  # users(+role), patient_profiles, mudras (+cache/jobs)
│   └── seeders/                     # DoctorSeeder, MudraSeeder, DatabaseSeeder(+demo patient)
├── resources/
│   ├── js/, css/                    # Vite entry points (Tailwind, Alpine)
│   └── views/
│       ├── components/              # x-alert, x-stat-card, x-application-logo, Breeze UI
│       ├── layouts/                 # app, guest, navigation
│       ├── auth/                    # Breeze auth screens (register customized)
│       ├── doctor/dashboard.blade.php   # placeholder (real UI in L2)
│       ├── patient/dashboard.blade.php  # placeholder (real UI in L3)
│       └── welcome.blade.php        # branded landing
├── routes/web.php                   # /, /dashboard (role redirect), doctor.*, patient.*
├── tests/Feature/                   # RegistrationTest, RoleAccessTest, Breeze auth, Profile
├── _reference/                      # ORIGINAL native-PHP app (read-only reference)
├── docs/                            # BRD/FRS/TDD/plan, coding standards, this file
├── pint.json                        # Pint preset=laravel, excludes _reference
└── .env / .env.example              # secrets + config (env-driven)
```

## Data model (L1)
| Table | Purpose | Key columns |
|---|---|---|
| `users` | doctors & patients | `role` (enum-backed, indexed), name, email, password |
| `patient_profiles` | patient demographics + ownership | `user_id` (unique FK), `doctor_id` (nullable FK), age, gender, phone, condition_notes |
| `mudras` | reference library | name, `slug` (unique), description, benefits, `ai_class_label`, reference_image_path, is_active |
| *(cache, jobs, sessions, password_reset_tokens)* | framework infra | — |

**Relationships:** `User hasOne PatientProfile`; `User(doctor) hasMany PatientProfile` (`assignedPatients`); `PatientProfile belongsTo User` (user & doctor).
**Tables L2+ will add:** `prescriptions` (L2), `practice_sessions` + `completions` (L4).

## Routes (L1)
| Method | URI | Name | Guard |
|---|---|---|---|
| GET | `/` | — | public |
| GET | `/dashboard` | `dashboard` | auth, verified → redirects by role |
| GET | `/doctor/dashboard` | `doctor.dashboard` | auth, verified, role:doctor |
| GET | `/patient/dashboard` | `patient.dashboard` | auth, verified, role:patient |
| — | Breeze auth + `/profile` | — | per Breeze |

## Conventions in force (see `docs/CODING-STANDARDS.md`)
- Thin controllers; business logic in Actions/Services; validation in Form Requests; authorization in middleware/policies.
- `declare(strict_types=1)`, typed signatures, enums over magic strings, config over hardcoded values.
- Blade components for reusable UI; assets via Vite (run `npm run build` after adding new Tailwind classes); no inline JS.
- Pint (laravel preset) clean; feature tests per module.

## Quality gate at L1
- ✅ 31 tests passing (77 assertions) · ✅ Pint clean · ✅ public pages 200 · ✅ role isolation verified.

---

## 🔒 L2 — Prescription Management (frozen · tag `v0.2.0-l2`)
L2 is **frozen**: no structural/functional changes except bug fixes.

**Added**
- Table `prescriptions` (patient_id, doctor_id, mudra_id, scheduled_time, duration_min, `start_date`, notes, `status`). Verification tuning (hold/confidence) is **not** stored — read from `config/practice.php`.
- `App\Enums\PrescriptionStatus` (active/completed/expired/cancelled; only `active` used).
- `App\Models\Prescription` (+ additive `User::prescriptions()` patient relation).
- `App\Policies\PrescriptionPolicy` (update/delete = owner + active) and `manage-patient` Gate (`AppServiceProvider`).
- `App\Http\Requests\Doctor\{Store,Update}PrescriptionRequest`.
- `App\Services\Prescription\PrescriptionService` (create / update / cancel).
- Controllers: `Doctor\DashboardController` (real, own-panel), `Doctor\PatientController@show`, `Doctor\PrescriptionController` (store/update/destroy).
- Views: `doctor/dashboard` (panel list), `doctor/patients/show` (info + add form + active list with Alpine inline edit).
- `PrescriptionSeeder` (demo patient → Pataka 08:00, Mushti 18:00).

**Routes (added, `role:doctor`)**
| Method | URI | Name |
|---|---|---|
| GET | `/doctor/patients/{patient}` | `doctor.patients.show` |
| POST | `/doctor/patients/{patient}/prescriptions` | `doctor.prescriptions.store` |
| PUT | `/doctor/prescriptions/{prescription}` | `doctor.prescriptions.update` (time/duration/notes only) |
| DELETE | `/doctor/prescriptions/{prescription}` | `doctor.prescriptions.destroy` (cancel) |

**Rules:** doctor acts only within their own panel (Gate + Policy → 403 otherwise); edits/cancels allowed only on `active` prescriptions; mudra & start_date immutable after creation.

**Quality gate at L2:** ✅ 44 tests (113 assertions) · ✅ Pint clean · ✅ assets built · ✅ end-to-end smoke verified.

---

## 🔒 L3 — Patient Module + Architecture Refinements (frozen · tag `v0.3.0-l3`)
L3 is **frozen**: no structural/functional changes except bug fixes.

**Patient features**
- `practice_sessions` table (read model; L4 writes it via AI verification) + `App\Enums\PracticeStatus`.
- `prescriptions.end_date` (nullable, via new migration — L2 migration untouched). "Active today" = `status=active` AND `start_date ≤ today` AND (`end_date` null OR `today ≤ end_date`) — `Prescription::scopeActiveOn`.
- Patient screens: dashboard ("Today's Therapy"), prescription detail, **placeholder** practice page (real screen in L4), history (sessions + streak + **Last Practice Date**).
- Routes `patient.{prescriptions.show, practice.show, history}`; nav "History" link.

**Architecture patterns introduced (apply going forward)**
- **Repositories** (`app/Repositories/`): `PrescriptionRepository`, `PracticeSessionRepository` — data access for the patient services.
- **DTOs** (`app/DTOs/`): `TodayTherapy`, `TodaySummary`, `DueMudra`, `HistoryStats` — services return typed objects, not associative arrays.
- **Policies replace ad-hoc gates**: `PrescriptionPolicy@view` (patient owns), `UserPolicy@manage` (doctor owns patient). The `manage-patient` / `view-own-prescription` gates were removed.
- **Domain events** (`app/Events/`): `PatientRegistered`, `PrescriptionCreated`, `PracticeVerified` (last dispatched in L4) → **listeners** (`app/Listeners/`) write **structured logs** to the `business` log channel.
- **Config, not constants**: `config/practice.php` now also holds `history_limit`.
- **Reusable Blade components**: `<x-card>`, `<x-badge>` (plus existing `<x-stat-card>`, `<x-alert>`) applied across patient + doctor views.

**Quality gate at L3:** ✅ 61 tests (150 assertions) · ✅ Pint clean · ✅ assets built · ✅ patient e2e smoke verified.

## Demo accounts (seeded; password `password`)
| Role | Email |
|---|---|
| Doctor | `anjali@kathak.test`, `ravi@kathak.test` |
| Patient | `patient@kathak.test` |
