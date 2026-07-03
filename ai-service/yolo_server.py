"""Lightweight self-hosted YOLO mudra classifier serving the /classify contract.

Runs on the host (conda env with ultralytics) and speaks the exact response shape
the Laravel MediapipeInferenceClient already expects, so nothing on the Laravel
side changes. Loads the trained classifier from models/kathak.pt.

Run (from ai-service/):
  python yolo_server.py         # serves on 0.0.0.0:8001
"""

import io
import os
import time
from pathlib import Path

import uvicorn
from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from PIL import Image
from ultralytics import YOLO

API_KEY = os.environ.get("API_KEY", "change-me")
MODEL_PATH = Path(__file__).resolve().parent / "models" / "kathak.pt"

model = YOLO(str(MODEL_PATH))
CLASS_NAMES = model.names

app = FastAPI(title="kathak-yolo")


@app.get("/health")
def health():
    return {
        "status": "healthy",
        "engine": "yolo",
        "model": MODEL_PATH.name,
        "classes": list(CLASS_NAMES.values()),
    }


@app.post("/classify")
async def classify(
    image: UploadFile = File(...),
    x_api_key: str = Header(default="", alias="X-API-Key"),
):
    if x_api_key != API_KEY:
        raise HTTPException(status_code=401, detail="unauthorized")

    started = time.perf_counter()
    raw = await image.read()
    if not raw:
        raise HTTPException(status_code=400, detail="empty image")

    img = Image.open(io.BytesIO(raw)).convert("RGB")
    result = model.predict(img, verbose=False)[0]
    top1 = int(result.probs.top1)
    confidence = float(result.probs.top1conf)
    label = CLASS_NAMES[top1]
    processing_ms = int((time.perf_counter() - started) * 1000)

    return {
        "success": True,
        "prediction": {"label": label, "confidence": round(confidence, 4)},
        "hands_detected": 1,
        "processing_time_ms": processing_ms,
    }


if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8001, log_level="info")
