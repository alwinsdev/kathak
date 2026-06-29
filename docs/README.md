# Documentation Index — Siddha Mudra Therapy

Authoritative documentation for the POC (`v1.0.0-poc`).

## Start here
- [../README.md](../README.md) — project overview, install, configuration, demo logins
- [DEPLOYMENT-GUIDE.md](DEPLOYMENT-GUIDE.md) — production deployment & security checklist

## Architecture & standards
- [PROJECT-STRUCTURE.md](PROJECT-STRUCTURE.md) — architecture, folder map, data model, per-module freeze notes (L1–L3 + refinements)
- [CODING-STANDARDS.md](CODING-STANDARDS.md) — development standards, layering, naming, the per-module workflow, established patterns
- [LARAVEL-POC-PLAN.md](LARAVEL-POC-PLAN.md) — the POC scope, decisions, and module plan

## Module designs
- [modules/L3-PATIENT.md](modules/L3-PATIENT.md) — Patient module design
- [modules/L4-AI-VERIFICATION.md](modules/L4-AI-VERIFICATION.md) — AI Practice & Verification design (camera, inference proxy, hold timer, verification flow, sequence diagram)

## Quality & release
- [MANUAL-QA-CHECKLIST.md](MANUAL-QA-CHECKLIST.md) — manual QA scenarios for the live camera + Roboflow path
- [PRODUCTION-READINESS-REVIEW.md](PRODUCTION-READINESS-REVIEW.md) — evidence-based audit (L1–L4), scores, refactor proposals
- [RELEASE-v1.0.0-poc.md](RELEASE-v1.0.0-poc.md) — final release summary: architecture, schema, API, tests, statistics, roadmap

## Archive
- [archive/](archive/) — superseded pre-POC enterprise planning docs (00–07, M0), kept for history only

---
### Module / version history
| Tag | Milestone |
|---|---|
| `v0.1.0-l1` | Foundation & Auth |
| `v0.2.0-l2` | Prescription Management |
| `v0.3.0-l3` | Patient Module + architecture refinements |
| (L4, phased) | AI Practice & Verification |
| `v1.0.0-poc` | Functional POC freeze |
