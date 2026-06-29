# V2 Technical Design — Self-Hosted YOLOv11 Inference
**Project:** Siddha Mudra Therapy · **Branch:** `feature/yolov11-ai` · **Date:** 2026-06-29
**Author:** AI Architect / Senior ML Engineer · **Status:** Design blueprint (no code)

> **Scope guard:** This replaces the *inference engine only*. The Laravel application, its layered architecture, business workflow, DTOs, Policies, Events, `VerifyPracticeAction`, `PracticeHoldTracker`, `PracticeSessionService`, repositories, and tests are **unchanged**. The single substitution is `RoboflowInferenceClient → YOLOInferenceClient`, behind the existing `App\Domain\AI\Contracts\InferenceClient` abstraction.

---

## 1. Executive Summary
The POC proved the verification loop using Roboflow's managed inference. Two limitations surfaced: (a) **~1.5 s per-frame latency** (serverless cold/warm), and (b) **no control over the model's class vocabulary**, which caused pose↔label mismatches. V2 replaces Roboflow with a **self-hosted YOLOv11 model served by a FastAPI microservice**.

**Why this is low-risk:** the codebase already isolates inference behind `InferenceClient::detect(string $imageBinary): InferenceResult`. We add **one new class** (`YOLOInferenceClient`), a **config-driven driver switch**, and **one unit test**. Nothing else in Laravel changes. The migration is branch-isolated and **instantly reversible** by flipping a config flag back to `roboflow`.

**Recommended engine:** **YOLOv11s** (object detection, 640 px), served via FastAPI in **Docker on a Linux host with an NVIDIA GPU** (ONNX/OpenVINO CPU fallback). Expected per-frame round-trip **~30–50 ms** (vs ~1500 ms today) and full ownership of the class vocabulary — which **structurally eliminates** the v1 naming-mismatch problem by making the trained class names the single source of truth, mirrored into `mudras.ai_class_label`.

## 2. Architecture Diagram
```
┌─────────┐  frame (JPEG)   ┌──────────────────────── Laravel (UNCHANGED) ───────────────────────┐
│ Browser │ ───────────────▶│ POST /patient/practice/sessions/{id}/detect  (thin controller)     │
│ camera  │◀─── JSON ────── │   → DetectFrameRequest (validate, policy)                          │
└─────────┘                 │   → VerifyPracticeAction (PURE: inference → match → DetectionResult)│
                            │        │ uses                                                       │
                            │        ▼                                                            │
                            │   InferenceClient (interface)                                       │
                            │        │ bound to                                                   │
                            │        ▼                                                            │
                            │   ★ YOLOInferenceClient (NEW) ── HTTP ──┐                            │
                            │   PracticeHoldTracker (cache, server-authoritative)                 │
                            │   PracticeSessionService (exactly-once completion) → DB             │
                            │   PracticeVerified event → listeners (business log)                 │
                            └──────────────────────────────────────────│─────────────────────────┘
                                                                        ▼
                                          ┌──────────── FastAPI service (NEW, private) ───────────┐
                                          │ POST /predict  (X-API-Key, X-Correlation-ID)          │
                                          │   → validate image → YOLOv11s.predict() → format JSON │
                                          │ GET /health                                           │
                                          │ YOLOv11s (best.pt / ONNX) loaded once, GPU/CPU        │
                                          └───────────────────────────────────────────────────────┘
```
The two swap points — `InferenceClient` (Laravel) and `/predict` (FastAPI) — mean the *engine* can change without the application knowing.

## 3. Component Design

### 3.1 `YOLOInferenceClient` (NEW — `app/Domain/AI/Clients/`)
- Implements `InferenceClient`. Method `detect(string $imageBinary): InferenceResult`.
- POSTs the **raw frame** (multipart) to `config('services.yolo.base_url').'/predict'` with `X-API-Key` and `X-Correlation-ID` headers; timeout from config (low — local service).
- Maps the FastAPI JSON to the **existing** `MudraPrediction` / `InferenceResult` DTOs (no DTO changes).
- Wraps transport/HTTP errors in the existing `InferenceException`.
- Mirrors `RoboflowInferenceClient` in shape — so the existing `Http::fake()` test pattern applies directly.

### 3.2 Driver binding (`AiServiceProvider`)
- Bind `InferenceClient` based on `config('services.inference.driver')` (`roboflow` | `yolo`). Both client classes coexist; the active one is selected by env. This makes the cutover and rollback a one-line `.env` change.

### 3.3 FastAPI inference service (NEW — separate repo/dir `ai-service/`)
- Loads the YOLOv11 model once at startup; exposes `/predict` and `/health`. Stateless, single responsibility. Detailed in §7.

### 3.4 Unchanged components (explicitly)
`VerifyPracticeAction`, `PracticeHoldTracker`, `PracticeSessionService`, `PracticeSessionRepository`, `PrescriptionRepository`, all DTOs, `PrescriptionPolicy`/`PracticeSessionPolicy`/`UserPolicy`, `PatientRegistered`/`PrescriptionCreated`/`PracticeVerified` events + listeners, `DetectFrameRequest`, the detect/start controllers, and `FakeInferenceClient`. **No edits.**

## 4. AI Architecture

### 4.1 Task framing — detection, not classification
Use **object detection** (one bbox per hand, class = mudra), matching the existing contract (`MudraPrediction{class, confidence, x, y, width, height}`) and the overlay's bounding-box rendering. Detection also localizes the hand → robust to background clutter and multiple hands, directly addressing the v1 failure mode where a busy background/whole-frame classification produced spurious labels.

### 4.2 Variant comparison (YOLOv11)
| Variant | Params | GFLOPs | COCO mAP50-95* | GPU latency @640† | Best for |
|---|---|---|---|---|---|
| YOLOv11n | ~2.6 M | ~6.5 | ~39.5 | ~2–5 ms | edge/mobile/CPU, max speed |
| **YOLOv11s** | **~9.4 M** | **~21.5** | **~47.0** | **~5–12 ms** | **real-time + high accuracy on a medium GPU** |
| YOLOv11m | ~20.1 M | ~68 | ~51.5 | ~12–25 ms | max accuracy, higher VRAM/latency |
*Generic COCO reference, not our task. †Indicative on T4/RTX 3060-class GPUs at FP16.

### 4.3 Recommendation: **YOLOv11s** — and why
- **Mudras are large, distinct objects** (a single hand fills much of the frame). This is an *easier* detection problem than COCO's small/varied objects, so the extra capacity of `m` yields little accuracy benefit while costing 2–3× latency and VRAM.
- **Real-time browser camera** needs low, predictable latency; `s` at ~5–12 ms leaves generous headroom under the per-frame budget (we sample every 300–500 ms), keeping round-trip ~30–50 ms.
- **Medium GPU fit:** `s` runs comfortably in 4–8 GB VRAM; `m` is feasible but unnecessary; `n` risks under-fitting the *visually similar* mudras (fist variants: Mushti vs Shikhara) where capacity helps discrimination.
- **High accuracy:** with a well-curated dataset (§5), `s` reaches high mAP on ~25 distinct hand poses; if the confusion matrix later shows persistent fist-variant confusion, the upgrade path to `m` (or more data) is a config/model swap — no Laravel change.
- **Net:** `s` is the accuracy/latency sweet spot for this task and hardware. Start with `s`; treat `m` as a contingency driven by evidence (confusion matrix), not assumption.

### 4.4 Inference resolution
Train and serve at **640×640** (letterboxed). Standard YOLO input; best documented accuracy/speed trade-off; matches client-side 640 px downscale (reduces upload + inference).

## 5. Dataset Design

### 5.1 Directory layout (Ultralytics format)
```
dataset/
├── train/
│   ├── images/   # *.jpg
│   └── labels/   # *.txt (YOLO format)
├── valid/
│   ├── images/
│   └── labels/
├── test/
│   ├── images/
│   └── labels/
└── data.yaml     # paths + class names (single source of truth)
```

### 5.2 `data.yaml`
```
path: ./dataset
train: train/images
val: valid/images
test: test/images
nc: 26
names: [pataka, tripataka, ardhpataka, kartarimukh, mayur, ardhachandra, aral,
        shuktund, mushti, shikhar, ... ]   # canonical tokens (see §6 mudra classes)
```

### 5.3 YOLO annotation format
One `.txt` per image, one line per object:
```
<class_id> <cx> <cy> <w> <h>      # all normalized to [0,1], center-based
```
Typically **one hand per training image**, but allow multi-hand images (one line each) to teach localization.

### 5.4 Class naming convention (critical — fixes the v1 problem)
- **Lowercase, ASCII, no spaces, stable tokens** (e.g., `mushti`, `shikhar`, `shuktund`).
- The class list in `data.yaml` is the **single source of truth**. It is mirrored verbatim into Laravel `mudras.ai_class_label` (seeder). A test/CI check asserts the two lists match. **Because we now control both training labels and the app's labels, the pose↔label mismatch from v1 cannot recur.**
- Display names (e.g., "Mushti") remain a separate human-facing field; the AI token is the machine key.

### 5.5 Data collection recommendations (justified)
| Aspect | Recommendation | Why |
|---|---|---|
| Resolution | Capture ≥ 720p; store/letterbox to 640 | Match inference size; avoid upscaling artifacts |
| Images/class | **300–500+ labeled** (min 150–200 to start) | Detection needs hundreds/class for robustness; more for visually-similar classes |
| Class balance | Keep counts within ~1.3× of each other | Prevents bias toward majority classes |
| Skin tone | Fitzpatrick I–VI represented per class | Fairness + generalization; avoids the bias seen in narrow datasets |
| Lighting | Indoor/outdoor, dim/bright, backlit, warm/cool | Real homes vary; biggest cause of v1 misfires |
| Background | Diverse, cluttered + plain; vary per class | **Prevents background overfitting** (the v1 model leaned on context) |
| Camera angle | Frontal primary + ±15–30° yaw/pitch | Patients won't be perfectly frontal |
| Distance/scale | Near and mid-distance hands | Hold position varies |
| Left/right hand | Collect both (or rely on h-flip aug, §6) | Patients use either hand |
| Negatives | Include "no-mudra"/random-hand frames (background images, no labels) | Reduces false positives between mudras |

### 5.6 Split
**70/20/10** train/valid/test, **stratified by class**, with **no subject leakage** (same person's frames must not span splits — prevents identity overfitting).

## 6. Training Strategy
Fine-tune the **pretrained YOLOv11s** (COCO weights) — transfer learning converges far faster than from scratch.

| Hyper-parameter | Recommendation | Justification |
|---|---|---|
| `imgsz` | 640 | Standard; matches inference; best accuracy/speed balance |
| `epochs` | 100–150 (with early stopping) | Enough to converge on a moderate dataset; ES prevents overfit |
| `patience` (early stop) | 25 (on val mAP50-95) | Stops when validation plateaus; saves time, avoids overfit |
| `batch` | Largest that fits VRAM (auto-batch; e.g., 16 @ 8–12 GB, 32 @ 16 GB+) | Stable gradients; VRAM-bound |
| `optimizer` | Start with Ultralytics auto (SGD+momentum); try AdamW if early loss is unstable | Defaults are well-tuned; AdamW can speed early convergence |
| `lr0` / `lrf` | 0.01 / 0.01 (SGD) with **cosine** schedule; warmup 3 epochs | Proven YOLO schedule; warmup stabilizes early steps |
| Augmentation | Mosaic (with `close_mosaic=10`), HSV (esp. value/lighting), scale, translate, ±10–15° rotate, mild blur; **h-flip ON** | Lighting/scale/bg robustness; close-mosaic restores fine detail late; h-flip covers left/right hands (mudras are mirror-equivalent — disable per-class only if a mudra is chirality-sensitive) |
| Validation metrics | mAP50, mAP50-95, **per-class P/R + confusion matrix** | Confusion matrix is the key tool to catch fist-variant confusion |
| Target | mAP50 ≥ 0.90 overall; no class < 0.80 recall | Acceptance gate before integration |
| Artifacts | Ultralytics emits `best.pt` (best val) + `last.pt`; **export `best.onnx`** | `best.pt` for GPU serving; ONNX for CPU/OpenVINO/portability |

**Iteration loop:** train → read confusion matrix → targeted data collection for confused pairs → retrain. Do **not** chase mAP blindly; optimize the *confused* classes.

## 7. FastAPI Service Architecture (design only)

### 7.1 Layout
```
ai-service/
├── app/
│   ├── main.py                 # app factory + lifespan (load model once)
│   ├── api/routes/
│   │   ├── predict.py          # POST /predict
│   │   └── health.py           # GET /health, GET /classes
│   ├── core/
│   │   ├── config.py           # pydantic Settings (env)
│   │   ├── logging.py          # structured JSON logs + correlation id
│   │   └── security.py         # X-API-Key dependency
│   ├── schemas/prediction.py   # Pydantic response/request models
│   └── services/
│       ├── model_loader.py     # singleton model, warmup
│       └── inference.py        # preprocess → predict → format
├── models/                     # best.pt / best.onnx (mounted, not baked if large)
├── tests/
├── requirements.txt
├── Dockerfile
└── .env.example
```

### 7.2 Dependencies (`requirements.txt`, pinned)
`fastapi`, `uvicorn[standard]`, `ultralytics`, `torch` (CUDA build for GPU) **or** `onnxruntime-gpu`/`onnxruntime`/`openvino` for CPU, `opencv-python-headless`, `pillow`, `numpy`, `pydantic`, `pydantic-settings`, `python-multipart`. (Poetry optional; requirements.txt is sufficient.)

### 7.3 Config (env via pydantic Settings)
`MODEL_PATH`, `DEVICE` (`cuda:0`|`cpu`), `IMG_SIZE=640`, `CONF_THRESHOLD` (low, e.g. 0.25 — Laravel applies the *business* threshold), `MAX_IMAGE_MB`, `API_KEY`, `LOG_LEVEL`.
> The provider returns *all* predictions above a low confidence; the **business** confidence threshold stays in Laravel (`VerifyPracticeAction` + `config('practice.confidence_threshold')`) — so verification policy remains in the app, unchanged.

### 7.4 Behaviour
- **Model loading:** load once in FastAPI lifespan; run a warmup inference; gate `/health` on success. Single worker per GPU (model is the shared resource).
- **/predict:** accept multipart `image` (or raw bytes); decode; run YOLO; return predictions + `processing_time_ms`. Propagate `X-Correlation-ID` into logs.
- **/health:** `{status, model_loaded, device, model_version, classes_count}` — for readiness/liveness.
- **/classes:** returns the class list (for the Laravel sync check).
- **Logging:** structured JSON, includes correlation id, latency, prediction count.
- **Errors:** typed → mapped to HTTP codes (§9).

## 8. Laravel Integration — exactly what changes

| Component | Change? | Detail |
|---|---|---|
| `InferenceClient` (contract) | **No** | Same method signature |
| `YOLOInferenceClient` | **NEW** | Implements the contract; maps FastAPI JSON → existing DTOs |
| `AiServiceProvider` | **Minimal** | Driver switch: bind contract → roboflow\|yolo by config |
| `config/services.php` | **Add** | `inference.driver`, `yolo.base_url`, `yolo.api_key`, `yolo.timeout` |
| `VerifyPracticeAction` | **No** | Still calls `$this->inference->detect()`, applies `confidenceFor(target)` |
| `PracticeHoldTracker` | **No** | Pure cache logic, engine-agnostic |
| `PracticeSessionService` | **No** | Completion/exactly-once unchanged |
| Repositories | **No** | — |
| DTOs (`InferenceResult`, `MudraPrediction`, `DetectionResult`, `HoldProgress`) | **No** | YOLO client maps into them |
| Policies | **No** | — |
| Events / Listeners | **No** | — |
| `DetectFrameRequest`, controllers | **No** | Thin controllers untouched |
| `FakeInferenceClient` + existing tests | **No** | Entire workflow/AI test suite keeps passing |
| Seeder `mudras.ai_class_label` | **Align** | Set to the trained class tokens (single source of truth) |
| Tests | **Add 1** | `YOLOInferenceClientTest` (Http::fake), mirroring the Roboflow client test |

**Conclusion:** This is the dividend of the InferenceClient abstraction validated in the Production Readiness Review — a true engine swap with a near-zero blast radius.

## 9. API Contract (Laravel ↔ FastAPI)

**`POST {YOLO_BASE_URL}/predict`**
- **Headers:** `X-API-Key: <secret>` (required), `X-Correlation-ID: <uuid>` (propagated), `Accept: application/json`.
- **Body:** `multipart/form-data` field `image` (JPEG/PNG). *(Multipart over base64: avoids ~33% payload inflation and a decode step.)*
- **200 response:**
```json
{
  "predictions": [
    { "class": "mushti", "confidence": 0.96, "bbox": [cx, cy, w, h] }
  ],
  "processing_time_ms": 18,
  "image_size": [width, height]
}
```
- **bbox semantics:** **center-based** `[cx, cy, w, h]` in **pixels of the submitted image** → maps directly to `MudraPrediction(x=cx, y=cy, width=w, height=h)`, which the existing overlay renders as `x - width/2`. (Contract chosen to require **zero** front-end overlay change.)

| Code | Meaning | Laravel handling |
|---|---|---|
| 200 | OK | parse → `InferenceResult` |
| 400 | Undecodable/bad image | `InferenceException` → controller 502 |
| 401 | Missing/invalid API key | `InferenceException` (alert: config error) |
| 413 | Image too large | `InferenceException` |
| 415 | Unsupported type | `InferenceException` |
| 422 | Validation error | `InferenceException` |
| 503 | Model loading/warming | `InferenceException` (transient) |
| 500 | Inference failure | `InferenceException` |

- **Timeout:** Laravel client timeout low (e.g., **3 s** — local service; vs 15 s for Roboflow).
- **Retry strategy:** **No per-frame app retry** — frames are sampled every 300–500 ms, so a dropped frame is naturally retried by the next one (the existing loop behaviour). At most an optional single fast retry on `connection refused` (service restart). Per-frame retries would queue stale frames and waste GPU.
- **Validation:** FastAPI enforces content-type, size, decodability, dimensions (defense in depth); Laravel's `DetectFrameRequest` already validates the browser upload.

## 10. Deployment

**Recommendation: FastAPI in Docker on Linux with an NVIDIA GPU; ONNX/OpenVINO CPU fallback.**

| Question | Recommendation | Why |
|---|---|---|
| Docker? | **Yes** | Reproducible CUDA/Python deps isolated from the Laravel/XAMPP host; portable; easy restart policy + health checks |
| GPU or CPU? | **GPU (NVIDIA) for production**; CPU acceptable for low concurrency/dev | YOLOv11s ~5–12 ms (GPU) vs ~50–150 ms (CPU). GPU gives the real-time win |
| Medium GPU? | RTX 3060 / T4 / A10 (4–8 GB) is ample | `s` is light; headroom for batch + other models |
| Linux vs Windows? | **Linux container** (best CUDA + Docker + NVIDIA Container Toolkit support) | Most stable GPU path; on Windows dev use Docker Desktop + WSL2 GPU, or run `uvicorn` directly |
| Windows Service? | Only if no Docker and Laravel is on Windows — wrap `uvicorn` with NSSM | Works, but Docker/Linux preferred for reproducibility |
| Topology | FastAPI **co-located** with Laravel (localhost) or same private VLAN; **never public** | Minimizes network latency; reduces attack surface |
| Scaling | 1 uvicorn worker per GPU; scale out with more GPU nodes behind an internal LB | Model is stateless; horizontal scaling is trivial |

Dev path (current XAMPP/Windows): run FastAPI via `uvicorn` locally (CPU, or local NVIDIA GPU); Laravel calls `http://127.0.0.1:8001/predict`.

## 11. Security
- **Laravel → FastAPI auth:** shared secret **API key** in `X-API-Key`. Stored in Laravel `.env` (`YOLO_API_KEY`) and FastAPI `.env` (`API_KEY`); never in code/git. FastAPI rejects missing/invalid keys with **401**. (For cross-host, add **mTLS**.)
- **Network isolation:** FastAPI bound to `localhost`/private network, firewalled; the **browser never reaches it** (browser → Laravel → FastAPI), preserving the same "secret never in the client" property as the Roboflow key.
- **Input hardening:** size/type/dimension caps at FastAPI **and** Laravel (`DetectFrameRequest`).
- **Rate limiting:** Laravel already throttles `detect` per user (`practice-detect`); FastAPI may add a per-key limiter as defense in depth.
- **Auditability:** correlation IDs propagated end-to-end (already in Laravel logs; FastAPI echoes them).
- **Model/file integrity:** model weights on a read-only mount; restrict who can replace them (supply-chain hygiene).

## 12. Performance

| Parameter | Recommendation | Rationale |
|---|---|---|
| Frame capture interval | 300–500 ms (config `detection_interval_ms`) | Local latency allows faster than Roboflow; still bounded by round-trip |
| Client image | Downscale to 640 px longest side, JPEG q0.6 | Smaller upload + faster inference; matches train size |
| Max request rate | GPU throughput ≫ demand (T4 ~100+ FPS); Laravel throttle caps per user | Protects GPU/cost without limiting UX |
| Expected per-frame round-trip | **~30–50 ms** (GPU) | ~5–12 ms inference + local network + Laravel overhead (vs ~1500 ms Roboflow) |
| Effective FPS per user | ~2–4 fps (interval-limited) | Sufficient for a hold-based UX |
| GPU VRAM | 4–8 GB | YOLOv11s inference is light |
| CPU fallback | YOLOv11s **ONNX/OpenVINO**, ~50–150 ms/frame | Usable for dev/low concurrency; GPU for production |

The hold timer (`PracticeHoldTracker`) already tolerates variable cadence, so lower latency simply makes the bar fill faster and the UX snappier — **no logic change** needed.

## 13. Testing

| Layer | Test | Notes |
|---|---|---|
| Laravel unit | `YOLOInferenceClientTest` (`Http::fake`) | Parse predictions → `InferenceResult`; error → `InferenceException`; `X-API-Key` sent. Mirrors `RoboflowInferenceClientTest` |
| Laravel workflow | **Unchanged** — `FakeInferenceClient` | All `VerifyPracticeAction`/hold/exactly-once/dashboard tests keep running engine-agnostic |
| FastAPI unit (pytest) | predict formatting, schema validation, health, error mapping; tiny/mocked model | No GPU needed in CI |
| Integration | Laravel ↔ live FastAPI (separate suite/CI stage) | Contract conformance; not in the hermetic unit CI |
| Mock inference | FastAPI `TEST_MODE` returns canned predictions | Integration without a GPU/model |
| Manual QA | Extend the existing checklist for the YOLO path | Accuracy across mudras × lighting × skin tone × hand |
| Benchmark | Latency (GPU/CPU), throughput (FPS), p95 round-trip; load test (locust) | Acceptance: p95 round-trip < 100 ms (GPU); mAP gate from §6 |

**Key property:** because the suite mocks at the `InferenceClient` seam (`FakeInferenceClient`), **the entire existing Laravel test suite remains green without touching it** — the V2 work only *adds* tests.

## 14. Risks & Mitigations
| Risk | Severity | Mitigation |
|---|---|---|
| Dataset quality (the real determinant of accuracy) | High | Diverse/balanced data (§5); confusion-matrix-driven iteration; acceptance gate |
| Similar-mudra confusion (fist variants) | Med | More targeted data; consider YOLOv11m; per-class recall gate |
| Class-list drift (train vs `ai_class_label`) | Med | `data.yaml` = single source of truth; `/classes` endpoint + CI sync check |
| Self-hosting ops burden (vs managed) | Med | Docker + healthcheck + restart policy; runbook |
| GPU cost/availability | Med | CPU/ONNX fallback; model is small |
| Model cold start | Low | Load at startup + warmup + `/health` gating |
| Latency if FastAPI remote | Low | Co-locate; private network |
| Version skew (model vs app) | Low | Version `/health`; pin model artifact; release notes |

## 15. Migration Plan from Roboflow (phased, gated, reversible)
- **Phase 0 — Design** *(this document)* → approval.
- **Phase 1 — Dataset:** collect + annotate per §5 (longest pole). Deliverable: versioned `dataset/` + `data.yaml`.
- **Phase 2 — Train:** fine-tune YOLOv11s → `best.pt`; evaluate (mAP, confusion matrix); export ONNX. Gate: §6 acceptance metrics.
- **Phase 3 — FastAPI service:** build `/predict` + `/health` + Docker; verify with curl + pytest + benchmark. Standalone, no Laravel yet.
- **Phase 4 — Laravel client:** add `YOLOInferenceClient` + config + driver switch + `YOLOInferenceClientTest`. Keep `RoboflowInferenceClient`. Default driver stays `roboflow`.
- **Phase 5 — Class alignment:** set `mudras.ai_class_label` to the trained tokens (seeder) + CI sync check.
- **Phase 6 — Integration & QA:** Laravel↔FastAPI integration tests, manual QA, benchmark vs Roboflow baseline.
- **Phase 7 — Cutover:** flip `INFERENCE_DRIVER=yolo`; monitor. **Rollback = set `=roboflow`** (instant, no deploy).

Each phase stops for review, matching the established module workflow. The POC on `master` (`v1.0.4-poc`) is never touched.

## 16. Future Scalability (no Laravel changes)
The dual seam — `InferenceClient` (Laravel) + `/predict` (FastAPI) — lets the engine evolve behind a stable contract:
- **ONNX / TensorRT / OpenVINO:** swap the *runtime inside FastAPI*; same `/predict` JSON → faster inference, **zero Laravel change**.
- **YOLOv12 / newer:** retrain, drop a new `best.pt` into FastAPI → **zero Laravel change** (contract preserved).
- **MediaPipe Hands:** run inside FastAPI as an alternative/booster engine (hand-landmark features can improve fine mudra discrimination), still behind `/predict`; or add a sibling `InferenceClient` if a different response shape is desired.
- **Edge / on-device / mobile:** run a light model (YOLOv11n/TFLite/MediaPipe) **in the browser or mobile app**; to preserve **server-authoritative verification**, the client posts *detections* (not just the decision) to Laravel, where `PracticeHoldTracker`/`PracticeSessionService` still own completion. This is a future `ClientInferenceClient` variant — again, only a new client behind the same contract.

**Architectural invariant:** Laravel never learns which engine runs. That is the Dependency-Inversion payoff this design preserves and extends.

---
### Appendix — Mudra classes (canonical tokens, ~26)
Aligned to the existing model vocabulary so the app's `ai_class_label` and the trained `data.yaml` names are identical:
`pataka, tripataka, ardhpataka, kartarimukh, mayur, ardhachandra, aral, shuktund, mushti, shikhar, kapitth, katak, hamsapaksha, sarpsheesh, mrighasheesh, kangul, trishool, padamkosh, sinhamukh, tamrachud, mukul, soochi, bhramara, chandrakala, hansaasya, chatur` — finalize the exact 20–30 set during Phase 1; each token is the single source of truth across training and Laravel.
