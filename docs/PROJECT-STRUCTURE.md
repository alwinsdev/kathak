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

## Demo accounts (seeded; password `password`)
| Role | Email |
|---|---|
| Doctor | `anjali@kathak.test`, `ravi@kathak.test` |
| Patient | `patient@kathak.test` |
