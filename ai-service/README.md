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
├── api/            # thin HTTP routes (health)
├── core/           # config, logging, exceptions, security
├── domain/         # business capabilities (library-agnostic)
│   ├── hand_landmarks/     # HandLandmarkService + HandLandmarkProvider (port)
│   ├── mudra_classifier/   # (later phase)
│   ├── explainable_ai/     # (later phase)
│   └── feedback/           # (later phase)
├── infrastructure/ # implementation details
│   └── providers/mediapipe/  # MediaPipeHandLandmarkProvider + ModelLoader
├── middleware/     # correlation-id propagation
├── schemas/        # pydantic response models
└── tests/
```
The **domain** depends on the `HandLandmarkProvider` port; **MediaPipe lives only
in infrastructure** and can be replaced without touching the domain.

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

## Prediction (Phase 2)
`POST /predict` — **protected** (`X-API-Key`). Multipart field `image` (JPEG/PNG/WebP/BMP).
Returns the detected hands with **21 3D landmarks**, handedness, and a bounding box:
```json
{
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
- **`bbox` is an *approximate*, center-based pixel box derived from the landmark
  extents — not a native object-detection box.** Don't assume pixel-perfect accuracy.
- This is the **frozen contract** consumed by Laravel; later phases extend it additively.
- Errors: `400` empty/undecodable image · `413` too large (size or dimensions) ·
  `415` unsupported format · `401` bad/missing key · `422` missing field · `503` model not ready.
- **Scope:** perception only — no mudra classification / explainable feedback yet.

Example:
```bash
curl -s -X POST http://localhost:8001/predict \
  -H "X-API-Key: $API_KEY" -F "image=@hand.jpg"
```

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
