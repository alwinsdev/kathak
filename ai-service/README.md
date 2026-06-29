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
