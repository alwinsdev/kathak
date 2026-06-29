# Module L4 — AI Practice & Verification — Design (for approval)
**Status:** Design — **no code until approved** · **Date:** 2026-06-29
**Builds on:** L1 (auth), L2 (prescriptions), L3 (patient module, `practice_sessions`, `PracticeVerified` event).
**This is the core of the POC.**

---

## 0. Headline
The patient opens the (now real) practice screen for a prescribed mudra. The browser streams camera frames to a **server-side inference proxy** → Roboflow. The backend compares the detected class to the prescribed mudra's `ai_class_label`, and when the correct mudra is **held above the confidence threshold for the required seconds**, the session is **auto-verified and recorded** — no manual completion. The dashboard then shows "Done".

**Key design stance: the hold timer and verification decision are SERVER-AUTHORITATIVE.** The browser only streams frames and renders feedback; it cannot self-declare success. This is essential because completion is the clinical signal of the POC.

**No schema change.** L3's `practice_sessions` already has every column L4 needs (`status`, `started_at`, `completed_at`, `best_confidence`, `detected_class`, `practiced_on`). Transient hold progress lives in the **cache**, not the DB. → L4 touches no frozen migration.

---

## 1. Business workflow (end-to-end)
1. Patient dashboard → **Practice** on a due prescription → `GET /patient/practice/{prescription}` (real screen replaces the L3 placeholder).
2. Screen loads target mudra (name, reference), and config-driven `hold_seconds`, `confidence_threshold`, `detection_interval_ms`.
3. Patient grants camera permission; live video shows.
4. JS calls **start** → server creates an `in_progress` `practice_session` (or returns today's existing verified one) and returns its id.
5. JS detection loop: every `detection_interval_ms`, capture a frame → `POST …/detect` (multipart JPEG).
6. Proxy authorises (owns session), validates the image, runs inference server-side, evaluates match vs target, updates **server-side hold state**, returns a normalised `DetectionResult` (`matched`, `confidence`, `detectedClass`, `heldSeconds`, `holdSeconds`, `verified`, `predictions[]`).
7. When server hold ≥ `hold_seconds`: finalise → `status=verified`, `completed_at`, `best_confidence`, `detected_class`; dispatch `PracticeVerified`; clear cache. `verified:true` returned.
8. JS shows success, stops the camera, links back to dashboard. Dashboard now renders **Done** (L3's `verifiedPrescriptionIdsOn` already powers this).

## 2. Complete AI verification flow (state machine)
```
in_progress ──(matched frame, hold reached)──► verified  (terminal, recorded)
     │
     └──(explicit stop / navigates away)──────► abandoned (terminal, optional)

Per /detect frame:
  inference → matched? (class==target AND confidence≥threshold)
     matched=true  → hold.accumulate(serverDelta); if hold≥target → finalize+verify
     matched=false → hold.reset()
  always → return DetectionResult
```

## 3. Camera / WebRTC lifecycle (browser)
- `getUserMedia({ video: { facingMode: 'user' } })` on **Start** (user gesture → permission).
- `loadedmetadata` → size the capture + overlay canvases to `videoWidth/Height`.
- Loop: `setTimeout(interval)`; each tick `drawImage(video→canvas)` → `canvas.toBlob('image/jpeg', q)` → POST. A `busy` flag prevents overlapping requests.
- Overlay canvas draws bounding boxes from returned `predictions[]`.
- **Teardown** (stop all tracks): on verified, on Stop, on `visibilitychange→hidden`, on `pagehide/unload`. Prevents the camera staying live.
- Errors: permission denied / no device / track ended → clear status message, offer retry.

## 4. Roboflow integration architecture
```
DetectController → VerifyPracticeAction → InferenceClient (interface)
                                              └► RoboflowInferenceClient (HTTP, key from config)
Tests bind FakeInferenceClient (no network, scripted predictions).
```
- **`App\Services\Inference\InferenceClient` interface**: `detect(string $imageBinary): InferenceResult`.
- **`RoboflowInferenceClient`** implements it: `Http::timeout()->post(config('services.roboflow.model_url') . '?api_key=' . config('services.roboflow.key'), base64)`. Parses Roboflow JSON → `InferenceResult`. Wraps transport/HTTP errors in `InferenceException`.
- Bound interface→impl in a small `InferenceServiceProvider` (or `AppServiceProvider::register`).
- **Key never leaves the server.** The browser only ever talks to our `/detect` route.

## 5. Target mudra matching algorithm
- `target = prescription.mudra.ai_class_label` (normalised: trim + case-insensitive).
- From `predictions[]`, select those whose class matches `target`; `matchConfidence = max(confidence)` among them (0 if none).
- `matched = matchConfidence ≥ config('practice.confidence_threshold')` (0.75 default; per-prescription override intentionally deferred per L2).
- A different mudra detected with high confidence is **not** a match — we only score the target class.

## 6. Confidence calculation strategy
- Per frame: `matchConfidence` as above.
- `best_confidence` (persisted on the session at finalisation) = running max of `matchConfidence` over the verifying run (kept in cache hold-state, written once on verify).
- Threshold is global (config), not per-prescription (consistent with L2 refinement #1).

## 7. Hold timer implementation (server-authoritative, cache-backed)
- Cache key `practice:hold:{sessionId}` → `HoldState { accumulatedMs, lastMatchedAtMs, bestConfidence }`, short TTL (e.g. 5 min).
- On **matched** frame at server time `now`:
  - `gap = now - lastMatchedAtMs`. If `lastMatchedAtMs` set and `gap ≤ maxGap` → `accumulatedMs += gap`; else **restart** (`accumulatedMs = 0`). `maxGap = detection_interval_ms × grace_factor` (config; tolerates jitter, restarts after a long pause).
  - `lastMatchedAtMs = now`; `bestConfidence = max(bestConfidence, matchConfidence)`.
  - If `accumulatedMs ≥ hold_seconds × 1000` → **finalise**.
- On **unmatched** frame → reset hold state.
- **Server time only** (never trust client timestamps). `heldSeconds` is returned so the UI can render a progress bar; it is informational, not authoritative.

## 8. Verification rules & failure handling
| Situation | Behaviour |
|---|---|
| Correct mudra held ≥ hold_seconds above threshold | **Verify** (finalise, event, Done) |
| Wrong / no mudra, or below threshold | `matched=false`, hold resets, UI prompts to show the target mudra |
| Long gap between matched frames (tab paused) | Hold restarts (grace window exceeded) |
| Already verified today for this prescription | Idempotent: `start` returns existing; `detect` short-circuits `verified:true` |
| Inference error / timeout | `/detect` returns `{ error: true }` (safe message, no key); UI shows transient notice, keeps looping with backoff |
| Camera denied / absent | Client-side error state; no session started |

## 9. Practice session lifecycle & persistence
- **start** → `PracticeSessionService.start(prescription, patient)`: if a verified session exists today → return it; else create `in_progress` (`practiced_on=today`, `started_at=now`).
- **detect** → `VerifyPracticeAction`: inference → match → hold update → maybe finalise.
- **finalise** → `PracticeSessionService.markVerified(session, bestConfidence, detectedClass)`: `status=verified`, `completed_at=now`; dispatch `PracticeVerified`.
- **abandon** (optional) → mark `abandoned` on explicit stop.
- Writes occur only at start and finalise — **not per frame** (per-frame state is cache-only).

## 10. Dispatching `PracticeVerified` & dashboard update
- `PracticeVerified` (defined in L3) dispatched at finalisation → auto-discovered `LogPracticeVerified` writes a structured `business` log line. Extensible for future streak/notification listeners.
- Dashboard "Done" needs **no new mechanism**: a verified session for today already flips `DueMudra.completedToday` via L3's `PatientScheduleService` + `PracticeSessionRepository.verifiedPrescriptionIdsOn`. The practice screen, on `verified:true`, simply links back; the next dashboard render shows Done. (No websockets — out of POC scope.)

## 11. Routes (patient, `role:patient`)
| Method | URI | Name | Purpose |
|---|---|---|---|
| GET | `/patient/practice/{prescription}` | `patient.practice.show` | real practice screen (replaces L3 placeholder) |
| POST | `/patient/practice/{prescription}/sessions` | `patient.practice.start` | create/get today's session → `{session_id}` |
| POST | `/patient/practice/sessions/{session}/detect` | `patient.practice.detect` | frame → inference → hold → maybe verify |
| POST | `/patient/practice/sessions/{session}/abandon` | `patient.practice.abandon` | optional explicit stop |

`detect`/`abandon` bind `{session}` (a `PracticeSession`); authorised by policy. `detect` also gets `throttle:practice` (config-driven rate limit).

## 12. Controllers (thin)
- `Patient\PracticeController@show` — render screen (target mudra + config values + route URLs as data attributes).
- `Patient\PracticeSessionController@start` — delegate to `PracticeSessionService.start`.
- `Patient\PracticeDetectionController@detect` — validate (FormRequest) → `VerifyPracticeAction` → return `DetectionResult` JSON.
- (`@abandon` on the session controller.)

## 13. Services / Actions
- **`VerifyPracticeAction`** (use-case): orchestrates inference + match + hold + finalise for one frame; returns `DetectionResult`; dispatches `PracticeVerified` on finalise. *(See §22 — recommended.)*
- **`PracticeSessionService`**: `start()`, `markVerified()`, `abandon()` (persistence + idempotency).
- **`PracticeHoldTracker`**: cache-backed hold accumulation (`record()`, `reset()`, `clear()`), clock injected for testability.
- **`RoboflowInferenceClient`** (impl of `InferenceClient`): the integration.

## 14. Repositories
- Extend **`PracticeSessionRepository`** (L3) additively: `currentInProgress(prescription, patient)`, `verifiedTodayFor(prescription)`. *(Additive methods only — not a refactor of L3 behaviour.)*

## 15. DTOs
- `InferenceResult` (predictions: `MudraPrediction[]`, topClass, topConfidence).
- `MudraPrediction` (class, confidence, x, y, width, height) — for overlay boxes.
- `DetectionResult` (matched, confidence, detectedClass, heldSeconds, holdSeconds, verified, error?) — JSON to the browser.
- `HoldState` (accumulatedMs, lastMatchedAtMs, bestConfidence) — internal to the hold tracker.

## 16. Events
- `PracticeVerified` (exists) — **now dispatched** on finalisation.

## 17. Policies
- **`PracticeSessionPolicy@update`** (patient owns the session) → used by `detect`/`abandon`.
- `PrescriptionPolicy@view` (exists, L3) → used by `show`/`start`.

## 18. Form Requests
- `DetectFrameRequest`: `image` required, mimetypes `jpeg,png`, `max:` from `config('practice.max_image_kb')`; `authorize()` via `PracticeSessionPolicy@update`.

## 19. Blade pages & JavaScript architecture
**Blade** `patient/practice/show.blade.php` (replaces placeholder): `<video>` + overlay `<canvas>`, status panel, **hold progress bar**, target mudra card, Start/Stop controls, success state. Carries `<meta name="csrf-token">` and `data-*` attributes (start URL, detect URL template, target name, hold seconds, interval, threshold). Uses `<x-card>`/`<x-badge>`.

**JS** (Vite modules under `resources/js/practice/`, no inline script):
- `camera.js` — `CameraController` (start/stop/captureFrame→Blob).
- `detector.js` — `DetectionLoop` (POST frames with CSRF header, backoff on errors, `busy` guard).
- `overlay.js` — draw bounding boxes.
- `practice.js` — entry: wires UI/Alpine state, calls start, runs loop, renders hold bar from `heldSeconds`, handles `verified`/errors, teardown. Registered as a Vite input.

## 20. Error handling & retry strategy
- **Inference errors:** `/detect` returns `{error:true}` (no internals). Loop continues; after *N* consecutive errors → show banner + exponential backoff (cap), keep camera running.
- **Network/CSRF/419:** refresh token via meta or reload prompt.
- **Throttled (429):** brief pause then resume.
- **Server hold robustness:** cache TTL + grace window means a dropped/slow frame just restarts the hold, never falsely verifies.

## 21. Security & performance
**Security:** auth + `role:patient` + `PracticeSessionPolicy` on every endpoint; **Roboflow key server-only**; CSRF on POSTs (header); `detect` rate-limited (config); image type/size/dimension caps; safe error messages (no key/stacktrace); `practiced_on`/timing from server clock; ownership re-checked on the session, not just the prescription.

**Performance:** configurable frame interval bounds Roboflow calls & cost; JPEG quality/size cap shrinks upload + inference latency; **per-frame state in cache, single DB write on verify**; client `busy` guard avoids pile-ups; short inference timeout; throttle protects server + Roboflow quota.

## 22. Architectural review: interfaces (DIP) & Action classes
You asked whether to add repository interfaces and use-case Actions. Measured recommendation:

| Proposal | Verdict | Why |
|---|---|---|
| **`InferenceClient` interface** (Roboflow behind an abstraction) | ✅ **Adopt** | High, concrete value: the **external, non-deterministic, costly** dependency. Lets every test inject a `FakeInferenceClient` (no network, scripted frames) and allows swapping providers later. This is textbook DIP at a real boundary. |
| **`VerifyPracticeAction`** use-case class | ✅ **Adopt** | The core algorithm (inference→match→hold→finalise) is a genuine single use-case. Isolating it keeps the controller thin, makes the AI-workflow unit-testable, and is reusable (future API/mobile). |
| **Interfaces for DB repositories** | ❌ **Skip** | Low value here: one implementation, already trivially testable via `RefreshDatabase` on in-memory SQLite. An interface adds indirection/files without measurable benefit — violates KISS and our "repositories only where they add value" rule. Revisit only if a second datasource appears. |
| Extra actions (e.g. `StartPracticeSession`) | ❌ **Skip** | `start`/`markVerified` are thin persistence concerns — a `PracticeSessionService` is clearer than action-per-method proliferation. |

Net: **abstract the volatile external boundary (inference), encode the core use-case as an Action, and keep persistence concrete.**

## 23. Testing strategy
- **Unit**
  - `VerifyPracticeAction` with `FakeInferenceClient` + `Carbon::setTestNow`: matched frame accrues hold; mismatch resets; reaching `hold_seconds` finalises (`verified`, event dispatched, `best_confidence` set).
  - `PracticeHoldTracker`: accumulation, reset-on-mismatch, restart-after-gap (injected clock).
  - `RoboflowInferenceClient` parsing via `Http::fake()` → `InferenceResult`; error → `InferenceException`.
- **Feature (HTTP)** — bind `FakeInferenceClient` in the container:
  - `start` creates `in_progress` (own prescription); 403 for others; idempotent when verified today.
  - `detect` validation (missing/oversize/wrong-type → 422/413/415); ownership 403; throttle 429.
  - `detect` happy path: scripted matched frames over advancing test-clock → session `verified`, `PracticeVerified` dispatched (`Event::fake`), idempotent thereafter.
  - Integration: after verification, patient dashboard shows **Done**.
- **AI-workflow test** (the important one): drive a **realistic frame sequence** through repeated `detect` calls — `[no-hand, wrong-mudra, match, match(gap→reset), match, match…]` — asserting verification fires at exactly the right moment and not before.
- **Browser (Dusk) — optional/limited:** real camera + Roboflow can't run headless reliably. Proposed: a Dusk smoke that loads the practice page and asserts the UI scaffold renders (camera/inference mocked); **full camera+AI is manual QA** (documented in the runbook). Flagged so coverage gaps aren't silent.

## 24. Sequence diagram (complete verification flow)
```
Patient    Browser(JS)        Laravel(detect)     VerifyPracticeAction   InferenceClient   Cache     DB        Event
  │  click Practice │               │                     │                   │            │        │          │
  │────────────────►│ GET show      │                     │                   │            │        │          │
  │                 │──────────────►│ render screen        │                   │            │        │          │
  │  allow camera   │ getUserMedia  │                     │                   │            │        │          │
  │────────────────►│               │                     │                   │            │        │          │
  │                 │ POST start ──►│ PracticeSessionService.start ───────────────────────────────► insert in_progress
  │                 │◄── {session_id}                      │                   │            │        │          │
  │                 │                                      │                   │            │        │          │
  │  (loop every interval)                                 │                   │            │        │          │
  │                 │ capture frame │                     │                   │            │        │          │
  │                 │ POST detect ─►│ authorize+validate ─►│ detect(image) ───►│ Roboflow   │        │          │
  │                 │               │                     │◄── InferenceResult │            │        │          │
  │                 │               │ evaluate match      │ hold.record() ─────────────────►│ get/put │          │
  │                 │               │                     │ held<target → return│            │        │          │
  │                 │◄── DetectionResult {matched,held,verified:false}         │            │        │          │
  │   (bar grows)   │ render bar/boxes                    │                   │            │        │          │
  │      ...        │      ...      │      ...            │      ...           │            │        │          │
  │                 │ POST detect ─►│ ───────────────────►│ hold.record(): held≥target      │        │          │
  │                 │               │                     │ markVerified() ────────────────────────► update verified
  │                 │               │                     │ PracticeVerified::dispatch ───────────────────────► listener→business log
  │                 │◄── DetectionResult {verified:true}  │ clear hold ────────────────────►│ forget  │          │
  │  success + stop │ stop tracks   │                     │                   │            │        │          │
  │────────────────►│ back to dashboard (shows "Done")    │                   │            │        │          │
```

## 25. Config additions (`config/practice.php`)
- `detect_rate_limit_per_minute` (e.g. 120), `hold_grace_factor` (e.g. 2.5), `jpeg_quality` (e.g. 0.7), `hold_cache_ttl` (e.g. 300). All env-overridable. (No new DB columns.)

## 26. Out of scope (L4)
Real-time push to other devices/websockets; multi-hand/sequence choreography; model training; per-prescription threshold overrides; offline practice. Manual completion remains **absent** by design.

## Estimate
High complexity (the POC core) · ~3–4 dev-days.

---
**Open decisions for you**
1. Adopt **`InferenceClient` interface** + **`VerifyPracticeAction`**, skip DB-repo interfaces? (recommended, §22)
2. **Server-authoritative hold via cache** (recommended) vs storing hold progress on the session row? (cache keeps the frozen table untouched and the record clean)
3. Optional **`abandon`** endpoint + **Dusk** smoke — include, or defer as manual QA?

**Awaiting your explicit approval before any L4 code.**
