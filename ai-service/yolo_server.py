"""Self-hosted mudra inference: MediaPipe hand-crop -> YOLO classification.

Pipeline per frame:
    webcam frame -> MediaPipe hand detection (Docker service) -> crop the hand
    -> YOLO classify the crop.

Cropping matters: the classifier was trained on hand-dominant images, so feeding
it the whole webcam scene (face + background) confuses it. We crop to the hand
first so inference matches training. Serves the same /classify contract Laravel
already calls, so nothing on the Laravel side changes.

Run (from ai-service/, conda env with ultralytics; MediaPipe detector on :8002):
  python yolo_server.py
"""

import io
import os
import time
from pathlib import Path

import requests
import uvicorn
from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from PIL import Image
from ultralytics import YOLO

API_KEY = os.environ.get("API_KEY", "change-me")
DETECT_URL = os.environ.get("MEDIAPIPE_DETECT_URL", "http://localhost:8002")
CROP_PADDING = 0.35  # expand the hand bbox by this fraction on each side
MAX_UPLOAD_BYTES = 5 * 1024 * 1024  # reject oversized frames (DoS guard)
MODEL_PATH = Path(__file__).resolve().parent / "models" / "kathak.pt"

model = YOLO(str(MODEL_PATH))
CLASS_NAMES = model.names

app = FastAPI(title="kathak-yolo")


def crop_to_hand(pil_img: Image.Image, raw: bytes):
    """Return (cropped_hand_image, crop_box) using the MediaPipe detector, or
    (None, None) if no hand is detected or the detector is unreachable."""
    try:
        resp = requests.post(
            f"{DETECT_URL}/landmarks",
            headers={"X-API-Key": API_KEY},
            files={"image": ("frame.jpg", raw, "image/jpeg")},
            timeout=5,
        )
        data = resp.json()
    except Exception:
        return None, None  # detector unreachable -> caller falls back

    hands = data.get("hands") or []
    if not hands:
        return None, "no_hand"

    bbox = hands[0]["bbox"]
    img_w, img_h = data["image_width"], data["image_height"]
    cx, cy, w, h = bbox["cx"], bbox["cy"], bbox["width"], bbox["height"]
    x1 = max(0, int(cx - w / 2 - w * CROP_PADDING))
    y1 = max(0, int(cy - h / 2 - h * CROP_PADDING))
    x2 = min(img_w, int(cx + w / 2 + w * CROP_PADDING))
    y2 = min(img_h, int(cy + h / 2 + h * CROP_PADDING))
    if x2 <= x1 or y2 <= y1:
        return None, "no_hand"
    return pil_img.crop((x1, y1, x2, y2)), (x1, y1, x2, y2)


@app.get("/health")
def health():
    return {
        "status": "healthy",
        "engine": "yolo",
        "model": MODEL_PATH.name,
        "crop": "mediapipe",
        "detect_url": DETECT_URL,
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
    if len(raw) > MAX_UPLOAD_BYTES:
        raise HTTPException(status_code=413, detail="image too large")

    frame = Image.open(io.BytesIO(raw)).convert("RGB")
    hand_img, crop_box = crop_to_hand(frame, raw)

    # No hand in view -> no prediction (don't classify the empty scene).
    if crop_box == "no_hand":
        return {
            "success": True,
            "prediction": None,
            "hands_detected": 0,
            "processing_time_ms": int((time.perf_counter() - started) * 1000),
        }

    # Detector unreachable -> fall back to the whole frame so detection still works.
    target = hand_img if hand_img is not None else frame
    result = model.predict(target, verbose=False)[0]
    label = CLASS_NAMES[int(result.probs.top1)]
    confidence = float(result.probs.top1conf)

    return {
        "success": True,
        "prediction": {"label": label, "confidence": round(confidence, 4)},
        "hands_detected": 1,
        "cropped": hand_img is not None,
        "processing_time_ms": int((time.perf_counter() - started) * 1000),
    }


if __name__ == "__main__":
    # Localhost only: this service is called by the Laravel backend on the same
    # machine and must never be reachable from the network.
    uvicorn.run(app, host="127.0.0.1", port=8001, log_level="info")
