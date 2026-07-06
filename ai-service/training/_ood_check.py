"""One-off gate check: out-of-distribution rejection for the new Siddha model.

Runs the old Bharatanatyam reference photos (all non-Aakash poses, including
the ring-finger pinch near-miss) through the production-style pipeline:
detector crop (:8002) -> new classifier weights. Every one must classify as
'other' with low aakash confidence.
"""

import io
import sys
from pathlib import Path

import requests
from PIL import Image
from ultralytics import YOLO

ROOT = Path(__file__).resolve().parents[1]
PHOTOS = Path("C:/xampp/htdocs/kathak/public/images/mudras")
model = YOLO(sys.argv[1] if len(sys.argv) > 1 else str(ROOT / "runs" / "siddha_cls" / "weights" / "best.pt"))
names = model.names


def crop(raw: bytes, img: Image.Image) -> Image.Image:
    try:
        r = requests.post(
            "http://localhost:8002/landmarks",
            headers={"X-API-Key": "change-me"},
            files={"image": ("f.jpg", raw, "image/jpeg")},
            timeout=5,
        )
        data = r.json()
        hands = data.get("hands") or []
    except Exception:
        hands = []
    if not hands:
        return img
    b = hands[0]["bbox"]
    W, H = data["image_width"], data["image_height"]
    x1 = max(0, int(b["cx"] - b["width"] / 2 - b["width"] * 0.35))
    y1 = max(0, int(b["cy"] - b["height"] / 2 - b["height"] * 0.35))
    x2 = min(W, int(b["cx"] + b["width"] / 2 + b["width"] * 0.35))
    y2 = min(H, int(b["cy"] + b["height"] / 2 + b["height"] * 0.35))
    return img.crop((x1, y1, x2, y2)) if (x2 > x1 and y2 > y1) else img


false_positives = 0
for photo in sorted(PHOTOS.glob("*.jpg")):
    raw = photo.read_bytes()
    img = Image.open(io.BytesIO(raw)).convert("RGB")
    result = model.predict(crop(raw, img), verbose=False)[0]
    label = names[int(result.probs.top1)]
    aakash_conf = float(result.probs.data[[k for k, v in names.items() if v == "aakash"][0]])
    flag = ""
    if label == "aakash" and aakash_conf >= 0.75:
        flag = "  <-- FALSE POSITIVE (would verify!)"
        false_positives += 1
    print(f"{photo.name:22s} -> {label:8s} aakash_conf={aakash_conf:.3f}{flag}")

print(f"\nFalse positives at the 75% gate: {false_positives}")
