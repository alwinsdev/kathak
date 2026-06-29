# 01 — Business Requirements Document (BRD)
**Project:** Siddha Mudra Therapy Platform
**Version:** 1.0 (Draft for approval) · **Date:** 2026-06-25

---

## 1. Purpose & vision
Deliver a secure, scalable platform that lets clinicians **prescribe Siddha mudra therapy** and lets patients **practice it correctly and consistently at home**, with **AI-assisted technique feedback** and **measurable adherence** that closes the clinical loop.

## 2. Business objectives
| ID | Objective | Success metric |
|---|---|---|
| BO1 | Improve home-exercise adherence | ≥ 70% average 30-day adherence across active patients |
| BO2 | Reduce in-clinic follow-up load | ≥ 30% of routine check-ins handled remotely via adherence reports |
| BO3 | Improve practice quality | ≥ 60% of completed sessions AI-verified above confidence threshold |
| BO4 | Protect patient trust & data | Zero P1 security incidents; consent captured for 100% of patients |
| BO5 | Scale to multiple clinicians safely | Each clinician sees only their own panel; onboard a new doctor in < 5 min |

## 3. Stakeholders
| Stakeholder | Interest |
|---|---|
| **Patient** | Clear schedule, reminders, easy practice with feedback, sense of progress |
| **Doctor / Therapist** | Fast prescribing, accurate adherence, early flag of non-adherent patients |
| **Clinic Admin** | Manage clinicians & mudra library, oversee system, run reports |
| **System Admin / IT** | Security, uptime, backups, integrations |
| **Data-science team** | Quality inference data (sessions, accuracy) for model improvement |

## 4. Scope

### In scope (Phases 0–3)
Role-based access (Patient/Doctor/Admin); secure auth (incl. forgot-password); doctor–patient panels; mudra library management; flexible prescriptions; AI-verified practice sessions; manual + verified completion; reminders (in-app/email/push); adherence analytics & exports; doctor↔patient feedback; consent & audit; PWA-grade responsive UI.

### Out of scope (this roadmap)
Native iOS/Android apps; in-app video teleconsult; billing/insurance; the ML model training pipeline; multi-clinic SaaS tenancy & marketplace (deferred to Phase 4).

## 5. Business capabilities (epics)
| Epic | Description | Business value |
|---|---|---|
| **E1 Security & Platform Foundation** | Close critical defects, secrets mgmt, RBAC, audit. | Mandatory; protects BO4 |
| **E2 Identity & Access** | Registration, login, password reset, profiles, admin user mgmt, doctor provisioning. | Enables onboarding (BO5) |
| **E3 Care Relationship** | Assign patients to doctors; panel ownership; consent. | Privacy + BO5 |
| **E4 Mudra Library** | Admin CRUD of mudras with reference media, categories, AI-class mapping. | Clinical content quality |
| **E5 Prescription & Scheduling** | Flexible plans: frequency, date range, multiple times, reps, duration. | Clinical fit (BO1) |
| **E6 Practice & AI Verification** | Live detection tied to session completion + accuracy scoring. | BO3, technique quality |
| **E7 Adherence & Reporting** | Accurate, time-aware adherence; trends; exports; non-adherence alerts. | BO1, BO2 |
| **E8 Engagement & Reminders** | Server-side reminders (email/push/in-app), streaks, feedback messaging. | BO1 |
| **E9 Analytics & Admin Ops** | System dashboards, data retention, backups. | Operations |

## 6. High-level business rules
- BR1 A patient is owned by exactly one **primary doctor** at a time (reassignable by admin); only that doctor (and admin) may view/edit their clinical data.
- BR2 Only **Admin** may create Doctor accounts and edit the mudra library.
- BR3 A patient **must accept consent** before clinical data is processed.
- BR4 A therapy session is "complete" for a day **only when AI-verified** — the model confirms the target mudra above the confidence threshold for the required hold time (locked policy D3). Manual completion is **not** a normal path; an **admin-controlled, audit-logged emergency override** is permitted only when inference is unavailable.
- BR5 Adherence is measured against the **schedule that was active on each given day** (historically accurate), not today's schedule.
- BR6 All clinical-data access and changes are **audit-logged**.
- BR7 Prescriptions are **soft-deactivated**, never hard-deleted (clinical record integrity).

## 7. Assumptions & constraints
- Browser with camera + WebRTC; HTTPS in production (camera/notifications require it).
- Hosting supports PHP 8.3 + Composer + a queue worker/cron (for reminders).
- Roboflow availability and rate limits bound real-time detection throughput/cost.
- Initial deployment: single clinic, single timezone (configurable later).

## 8. Risks
| Risk | Impact | Mitigation |
|---|---|---|
| Roboflow cost/abuse | $$ | Auth-gate proxy, per-user rate limits, frame throttling, size caps |
| Privacy/regulatory | Legal | Consent, RBAC ownership, audit, encryption at rest/in transit |
| Low AI accuracy frustrates users | Adoption | Keep manual completion fallback; show confidence; allow threshold tuning |
| Migration regression | Delivery | Strangler-fig migration; parity tests per module before cutover |

## 9. Release strategy
Incremental, **one module at a time**, each gated by your approval (per your directive). Phase 0 (security) ships first because it is a prerequisite for safely exposing anything else.
