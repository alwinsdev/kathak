# Manual QA Checklist — AI Practice & Verification (L4)
**Why manual:** the live camera + Roboflow path cannot be reliably automated. Backend logic, hold timing, idempotency and the detect endpoint are covered by the automated suite (`FakeInferenceClient` + `Http::fake`). This checklist covers the parts that need a real browser, camera, and Roboflow model.

## Pre-requisites
- `ROBOFLOW_API_KEY` and `ROBOFLOW_MODEL_URL` set in `.env`.
- Served over **HTTPS or `http://localhost`** (camera requires a secure context).
- Logged in as a patient with an active prescription due today (`patient@kathak.test` / `password`).
- Tail logs while testing: `storage/logs/business-*.log` (look for `correlation_id`, `inference_*`, `practice_verified`).

## Checklist

| # | Scenario | Steps | Expected |
|---|---|---|---|
| 1 | **Camera permission — allow** | Open Practice → press Start → Allow | Live feed shows; status "show your … mudra"; one in-progress session created |
| 2 | **Camera permission — deny** | Start → Block camera | Friendly "Camera unavailable…" message; no crash; Start re-enabled; no session left streaming |
| 3 | **Correct mudra** | Hold the prescribed mudra steadily | Hold bar fills; at `hold_seconds` → "✓ Verified! …"; camera stops; dashboard shows **Done** |
| 4 | **Wrong mudra** | Show a different mudra | Status "Detected X. Show your …"; hold bar stays empty; never verifies |
| 5 | **Low confidence** | Show the mudra partially / poor angle | No match while below threshold; hold does not advance |
| 6 | **Different lighting** | Repeat #3 in bright, dim, backlit | Verification still achievable in reasonable lighting; note failures for model tuning |
| 7 | **Hold then break** | Hold ~half the time, drop the pose, resume | Hold bar resets on break, then climbs again (grace window tolerates brief jitter) |
| 8 | **Network interruption** | Hold mudra, disable Wi-Fi briefly, re-enable | Status shows "Reconnecting…"; loop resumes; no false verification; no crash |
| 9 | **Roboflow timeout / error** | Temporarily set a bad `ROBOFLOW_MODEL_URL` | Detect returns graceful 502; UI shows "temporarily unavailable"; keeps trying; key never visible in network tab |
| 10 | **Browser refresh mid-practice** | Refresh during practice | Camera released; on reload Start works; in-progress session resumes (no duplicate) |
| 11 | **Already completed today** | After verifying, open Practice again | "Already completed today"; no second verification; **exactly one** `practice_verified` log line for that session |
| 12 | **Multiple tabs** | Open Practice in two tabs, verify in one | Other tab does not double-complete; event/log appears once |
| 13 | **Tab hidden / switch away** | Switch tabs mid-practice | Camera track released (check OS camera indicator off) |
| 14 | **Mobile browser** | Repeat #1, #3 on a phone (Chrome/Safari) | Front camera opens; layout usable; verification works |
| 15 | **Credential safety** | Inspect Network tab during detect | Only same-origin `/patient/practice/.../detect` calls; **no Roboflow URL or API key** in the browser |

## Sign-off
- Tester: ____________  Date: __________  Build/tag: __________
- Result: ▢ Pass ▢ Pass with notes ▢ Fail — notes: ______________________________
