# Development Standards — Siddha Mudra Therapy (Laravel)
**Status:** Authoritative · **Date:** 2026-06-29
**Principle:** A POC built to professional Laravel standards so it can grow into production **without an architectural rewrite.**

This document governs **every module**. Code that violates it is not "done."

---

## 1. Architecture principles
- **SOLID, DRY, KISS, Separation of Concerns**, Clean Architecture *where it adds value* — never ceremony for its own sake.
- **Thin controllers**: they orchestrate (validate → call service/action → return view/resource). No business rules in controllers.
- **Business logic lives in Services** (stateful/coordinating) or **Actions** (single use-case). Not in controllers, models, or Blade.
- **No business logic in Blade.** Views render; they don't decide.
- **Validation lives in Form Requests** — never inline in controllers.
- **Authorization lives in Policies/Gates** + route middleware.
- **Repository Pattern only where it earns its keep** (e.g. complex/duplicated queries or a likely datasource swap). Default to Eloquent in services. Do **not** wrap every model in a repository.

## 2. Layer responsibilities
| Layer | Responsibility | Must NOT |
|---|---|---|
| Controller | HTTP in/out, call service/action, return response | Hold business rules or queries |
| Form Request | Validation + authorization of the request shape | Mutate data |
| Service / Action | Business rules, transactions, orchestration | Touch `$request`/HTTP, echo views |
| Model (Eloquent) | Persistence, relationships, casts, scopes | Hold cross-entity business workflows |
| Policy / Gate | Can-this-user-do-this decisions | Validation |
| DTO | Typed data passed between layers | Persistence logic |
| Resource | Shape JSON output | Business rules |
| Blade + components | Presentation only | Logic / queries |

## 3. Project structure (feature-oriented where it helps)
```
app/
├── Actions/            # single use-case operations (e.g. VerifyMudraPractice)
├── DTOs/               # typed data carriers between layers
├── Enums/              # Role, Gender, PracticeStatus, etc. (PHP 8.1 enums)
├── Exceptions/         # domain exceptions
├── Helpers/            # pure helper functions (autoloaded)
├── Http/
│   ├── Controllers/{Doctor, Patient, Auth}/
│   ├── Middleware/      # EnsureRole, etc.
│   ├── Requests/        # Form Requests
│   └── Resources/       # API resources (when JSON is returned)
├── Models/
├── Policies/
├── Providers/
├── Services/{Roboflow, Practice, Adherence, Prescription}/
├── Support/            # framework-adjacent glue, value objects
└── Traits/             # shared reusable behavior
```
Controllers may be grouped by role namespace (`App\Http\Controllers\Doctor\...`). Keep namespaces clear and responsibilities separated. Don't create empty folders "just in case" — add a directory when the first real class needs it.

## 4. Naming standards
- **Models: singular** — `User`, `Mudra`, `Prescription`, `PracticeSession`, `Completion`, `PatientProfile`.
- **Controllers:** `DoctorController`, `PatientController`, `PrescriptionController`, `PracticeSessionController`.
- **Services:** `PrescriptionService`, `PracticeVerificationService`, `RoboflowService`, `AdherenceService`.
- **Actions:** verb-first — `VerifyMudraPractice`, `RecordCompletion`.
- **Form Requests:** `StorePatientRequest`, `AssignMudraRequest`, `StorePrescriptionRequest`.
- **Enums:** `Role`, `Gender`, `PracticeStatus`.
- **Methods:** meaningful, no abbreviations — `verifyHeldMudra()` not `vfyMudra()`.
- **DB:** tables plural snake_case (`practice_sessions`); columns snake_case; FKs `{singular}_id`; booleans `is_`/`has_`.

## 5. Database standards
- Proper **foreign keys** with explicit `onDelete` — cascade **only** where a child cannot exist without its parent (e.g. `patient_profiles` → `users`); otherwise `restrict`/`nullOnDelete`.
- **Indexes** on searchable / frequently-filtered / FK columns.
- **`timestamps()`** on every table.
- **Soft deletes only where business-meaningful** (e.g. `prescriptions` so history survives) — not blanket.
- **Never hardcode IDs** — resolve via slug/enum/query/relationship.
- Use migrations for all schema; never edit the DB by hand. Seeders for reference + demo data; Factories for tests/demo volume.

## 6. Configuration (no magic values)
All tunables live in **config files reading from `.env`** — never hardcoded:
- `config/services.php` → `roboflow.key`, `roboflow.model_url`
- `config/practice.php` (new) → `confidence_threshold` (default 0.75), `hold_seconds` (default 5), `detection_interval_ms`, `max_image_kb`, camera defaults
- Per-prescription overrides (threshold/hold) live in the DB column, defaulting from config.

## 7. Frontend standards
- **Blade components** for reusable UI (`<x-stat-card>`, `<x-alert>`, `<x-form.field>`, layout).
- **Vite** for all assets; **no inline `<script>`** blocks — JS lives in `resources/js/` modules.
- **Alpine.js** for lightweight interactivity; **vanilla JS modules** for camera/WebRTC + the detection loop.
- Always **escape output** (`{{ }}`); use `{!! !!}` only for trusted, deliberate HTML.
- Keep design tokens/CSS centralized (port the current look into a stylesheet, not inline styles).

## 8. Security standards
- Laravel **authentication** (Breeze) + **authorization** (Policies/Gates).
- **Every route protected** by appropriate auth + `role` middleware.
- **Validate all input** (Form Requests); **escape all output**.
- **API keys never reach the browser** — Roboflow is called server-side only.
- **CSRF** on all state-changing requests (Laravel default; keep it).
- Secrets in `.env`; `.env` git-ignored; `.env.example` committed without secrets.

## 9. Code quality
- `declare(strict_types=1);` in PHP class files; type-hint params and returns.
- Clean, self-explanatory code; **comment only non-obvious business logic** (the *why*, not the *what*).
- No duplication; extract shared logic to services/traits/helpers.
- Follow PSR-12; format with **Laravel Pint** before each module is marked done.
- Prefer enums over magic strings; prefer named methods over deep conditionals.

## 10. Per-module development workflow (strict order, one module at a time)
For every module:
1. **Explain the implementation approach** (and get approval to proceed).
2. Create **migration(s)**.
3. Create **models** (relationships, casts, scopes).
4. Create **Form Requests**.
5. Create **Services / Actions**.
6. Create **Controllers**.
7. Create **Blade views / components**.
8. Register **routes**.
9. **Seed** demo data.
10. **Test** the module (feature/unit as appropriate).
11. **Stop and wait for approval** before the next module.

> Never implement multiple modules at once. A module is "done" only when steps 2–10 pass and it adheres to §1–§9.

## 11. Definition of Done (every module)
- [ ] Adheres to architecture, naming, DB, config, frontend, security, and quality standards above.
- [ ] No business logic in controllers or Blade.
- [ ] All input validated via Form Requests; routes protected by middleware/policies.
- [ ] No hardcoded secrets or magic values.
- [ ] Pint-clean; tests for the module pass.
- [ ] Reviewed and approved by owner.
