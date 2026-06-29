# MediaPipe AI service — CPU image (Python 3.11)
FROM python:3.11-slim

ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1 \
    PIP_NO_CACHE_DIR=1 \
    # Quiet MediaPipe's native glog/absl chatter (keeps our JSON logs clean).
    GLOG_minloglevel=2

# System libs required by MediaPipe's native bindings (GL/GLES/EGL) and OpenCV;
# curl is used for the model download + container healthcheck.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libgl1 libglib2.0-0 libgles2 libegl1 libgomp1 curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY requirements.txt .
RUN pip install -r requirements.txt

# Download the MediaPipe HandLandmarker model.
RUN mkdir -p models \
    && curl -fsSL -o models/hand_landmarker.task \
       https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task

COPY pyproject.toml requirements-dev.txt ./
COPY app ./app
COPY .env.example ./.env

EXPOSE 8001

HEALTHCHECK --interval=30s --timeout=5s --start-period=25s --retries=3 \
    CMD curl -fsS http://localhost:8001/health || exit 1

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001"]
