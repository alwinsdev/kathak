# MediaPipe AI Service

Self-hosted hand-landmark AI inference service for **Siddha Mudra Therapy** (V2).
Built with FastAPI; uses **MediaPipe Hands** behind a domain abstraction.

> **Phase 1 (this milestone):** foundation only — app, config, logging, exception
> handling, security scaffold, MediaPipe initialization, Docker, and a `/health`
> endpoint. **No** hand detection, landmark extraction, classification, or
> prediction yet (later phases).

## Architecture (Clean Architecture)
```
app/
├── api/            # thin HTTP routes (health, landmarks)
├── core/           # config, logging, exceptions, security
├── application/    # use-case orchestration
│   └── mudra_classifier/   # ClassificationService (normalize→extract→classify)
├── domain/         # business capabilities (library-agnostic, dependency-free)
│   ├── geometry.py         # pure-Python 3D vector helpers (shared)
│   ├── hand_landmarks/     # HandLandmarkService + provider port; topology + normalization
│   ├── mudra_classifier/   # HandFeatures, feature extraction, MudraClassifier port,
│   │                       #   ClassificationRequest/Result, reserved metadata, exceptions
│   ├── explainable_ai/     # (later phase)
│   └── feedback/           # (later phase)
├── infrastructure/ # implementation details
│   ├── providers/mediapipe/   # MediaPipeHandLandmarkProvider + ModelLoader
│   └── classifiers/           # factory (single composition point) + providers
│       ├── stub/              #   StubMudraClassifier (placeholder)
│       └── rule_based/        #   RuleBasedMudraClassifier (open_palm / closed_fist)
├── middleware/     # correlation-id propagation
├── schemas/        # pydantic response models
└── tests/          # incl. fixtures/landmarks/ golden geometry fixtures
```
The **domain** depends on ports (`HandLandmarkProvider`, `MudraClassifier`);
**MediaPipe and the classifiers live only in infrastructure** and can be replaced
without touching the domain or application layers.

## Configuration (`.env`)
| Var | Default | Purpose |
|---|---|---|
| `ENVIRONMENT` | development | dev / production |
| `HOST` / `PORT` | 0.0.0.0 / 8001 | bind address |
| `LOG_LEVEL` | INFO | structured JSON logs |
| `API_KEY` | change-me | protects future inference routes |
| `REQUEST_TIMEOUT` | 5 | seconds (future) |
| `MODEL_PATH` | models/hand_landmarker.task | MediaPipe model |
| `MAX_HANDS` | 1 | hands to detect (future) |
| `DETECTION_CONFIDENCE` | 0.5 | min detection confidence (future) |
| `TRACKING_CONFIDENCE` | 0.5 | min tracking confidence (future) |

## Run with Docker (recommended)
```bash
cd ai-service
docker compose up --build        # builds image, downloads the model, starts on :8001
curl http://localhost:8001/health
docker compose down
```
Production: run the built `mediapipe-ai` image without the compose dev override
(the Dockerfile `CMD` runs a plain uvicorn server); inject env via your platform.

## Local run (without Docker)
Requires Python 3.11/3.12 (MediaPipe wheel availability) and the model file:
```bash
pip install -r requirements.txt
mkdir -p models && curl -fsSL -o models/hand_landmarker.task \
  https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task
cp .env.example .env
uvicorn app.main:app --reload --port 8001
```

## Health
`GET /health` →
```json
{
  "status": "healthy",
  "service": "mediapipe-ai",
  "version": "1.0.0",
  "model_loaded": true,
  "mediapipe_version": "0.10.x",
  "python_version": "3.11.x",
  "environment": "development",
  "uptime": "00:00:05"
}
```
Returns **503** with `status: unhealthy` until the model is loaded.

## Landmark extraction (Phase 2)
`POST /landmarks` — **protected** (`X-API-Key`). Multipart field `image` (JPEG/PNG/WebP/BMP).
This stage **only extracts hand landmarks** — it does not classify a mudra (later
phases may add `POST /classify` or `POST /recognize`). Returns the detected hands
with **21 3D landmarks**, handedness, and a bounding box:
```json
{
  "api_version": "1.0",
  "hands": [
    { "handedness": "Right", "score": 0.98,
      "bbox": { "cx": 320.0, "cy": 240.0, "width": 110.0, "height": 130.0 },
      "landmarks": [ { "x": 0.51, "y": 0.62, "z": -0.03 }, "...21" ] }
  ],
  "hands_detected": 1,
  "image_width": 640, "image_height": 480,
  "processing_time_ms": 14,
  "detected_at": "2026-06-29T10:00:00Z",
  "correlation_id": "..."
}
```
- **`api_version`** versions the response contract: additive fields keep `"1.0"`,
  a breaking change bumps it — so consumers can evolve safely.
- Every detected hand is guaranteed to carry **exactly 21 landmarks**; a malformed
  detection is rejected (`500 malformed_landmarks`) rather than returned.
- **`bbox` is an *approximate*, center-based pixel box derived from the landmark
  extents — not a native object-detection box.** Don't assume pixel-perfect accuracy.
- This is the **frozen contract** consumed by Laravel; later phases extend it additively.
- Errors: `400` empty/undecodable image · `413` too large (size or dimensions) ·
  `415` unsupported format · `401` bad/missing key · `422` missing field ·
  `500` malformed landmark data · `503` model not ready.
- **Scope:** perception only — no mudra classification / explainable feedback yet.

Example:
```bash
curl -s -X POST http://localhost:8001/landmarks \
  -H "X-API-Key: $API_KEY" -F "image=@hand.jpg"
```

## Mudra classification — foundation (Phase 3)
> **Foundation only — no recognition yet.** There is **no `/classify` endpoint**.
> This phase builds the provider-agnostic pipeline a real classifier will plug
> into: raw landmarks → normalized frame → explainable geometric features → a
> classifier *port* with a stub. No rule engine, ML model, explainable AI, or
> feedback generation.

Pipeline (all pure-Python, dependency-free domain code):
```
HandLandmarks (perception)
  → normalize()                     # domain/hand_landmarks/normalization.py
  → NormalizedHandLandmarks         # translation + scale + 2D-rotation invariant
  → FeatureExtractionService.extract()
  → HandFeatures                    # curls, finger angles, spreads, key distances
  → MudraClassifier.classify()      # port (domain/mudra_classifier/classifier.py)
  → StubMudraClassifier             # returns {"unrecognized", 0.0} (infrastructure)
```

- **Normalization** makes hand *shape* independent of position, size, and roll
  about the camera axis: the wrist moves to the origin, coordinates are scaled by
  the wrist→middle-MCP distance, and the hand is rotated in-plane so that axis
  points along **+Y**. Full 3D canonicalization (out-of-plane tilt) and handedness
  mirroring are **deferred** to a later phase.
- **`HandFeatures`** is intentionally **ML-agnostic** — named, explainable
  geometry (per-finger curl/direction, inter-finger spread, key distances) that a
  future rule-based or ML classifier can threshold or learn from.
- **`ClassificationResult`** = `label`, `confidence`, `reason`, `metadata` — the
  `metadata` bag lets later phases add detail without breaking the contract.
- **Golden fixtures** (`tests/fixtures/landmarks/`) lock normalization output so
  geometry changes can't silently regress; invariance is also asserted by
  transforming the input (translate/scale/rotate) and re-normalizing.

## Classification engine — pluggable architecture (Phase 4)
> **Architecture + one demo provider only.** No model training, no ML libraries
> (TensorFlow / PyTorch / ONNX), no explainable AI, **no `/classify` endpoint**,
> no Laravel. The rule-based classifier exists only to prove the pipeline runs.

```
ClassificationRequest(hand)
  → normalize() → FeatureExtractionService.extract()
  → MudraClassifier.classify()          # the Phase 3 port (provider abstraction)
  → ClassificationResult
```
`ClassificationService` (in `app/application/mudra_classifier/`) orchestrates the
flow and depends only on the `MudraClassifier` port — **providers swap without any
domain or application change.**

- **Provider selection is config-driven.** `MUDRA_CLASSIFIER_DRIVER` (default
  `rule_based`) is resolved by `infrastructure/classifiers/factory.py` — the
  **single composition point**. Today: `stub`, `rule_based`. Future drivers
  (`ml`, `tensorflow`, `onnx`, `pytorch`) register there and nowhere else; each
  implements the existing `MudraClassifier` contract (e.g. `RuleBasedMudraClassifier`,
  later `TensorFlowMudraClassifier`, `ONNXMudraClassifier`, ...).
- **`RuleBasedMudraClassifier`** recognizes only **`open_palm`** and
  **`closed_fist`** from the **mean** curl of the four non-thumb fingers (forgiving
  of one stray finger); anything in between is **`unknown`** (confidence `0.0`).
  Thresholds are illustrative — this is architectural validation, **not** full
  mudra recognition.
- **Reserved result metadata.** `ClassificationResult` is unchanged (frozen); the
  service guarantees its `metadata` carries the core keys `model_version`,
  `classifier_type`, `confidence`, and `prediction_timestamp` on every result, so
  the contract can grow without breaking. `confidence` also remains a first-class
  result field. Additional keys `classifier_name`, `classifier_version`, and
  `dataset_version` are **reserved but optional** — populated once we begin
  training and versioning custom models.
- **Validation:** null/empty landmarks and empty/incomplete feature vectors raise
  the typed `InvalidFeaturesError`; an unknown driver raises
  `UnknownClassifierDriverError`.

## Tests & lint
```bash
pip install -r requirements-dev.txt
ruff check . && ruff format --check .
pytest
```
Tests use a fake provider — they run without MediaPipe or a model file.

## Security
- `/health` is public (readiness probe). Future inference routes require `X-API-Key`.
- The service is intended to run **private** (localhost / internal network), never browser-facing.
