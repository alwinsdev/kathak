"""Merge cropped webcam captures into the existing 6-class dataset.

Each webcam frame is cropped to the hand (via the MediaPipe detector on :8002) so
the training data matches the crop-at-inference pipeline, then added to the train
split. Val/test stay as the existing dataset (held-out).
"""

import io
import shutil
from pathlib import Path

import requests
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "datasets" / "kathak_sub_cls"
WEBCAM = ROOT / "datasets" / "webcam"
MERGED = ROOT / "datasets" / "kathak_merged_cls"
DETECT = "http://localhost:8002"
PAD = 0.35


def crop_hand(raw: bytes, img: Image.Image) -> Image.Image:
    try:
        r = requests.post(
            f"{DETECT}/landmarks",
            headers={"X-API-Key": "change-me"},
            files={"image": ("f.jpg", raw, "image/jpeg")},
            timeout=5,
        )
        data = r.json()
        hands = data.get("hands") or []
    except Exception:
        hands = []
    if not hands:
        return img  # fallback: keep full frame
    b = hands[0]["bbox"]
    W, H = data["image_width"], data["image_height"]
    cx, cy, w, h = b["cx"], b["cy"], b["width"], b["height"]
    x1 = max(0, int(cx - w / 2 - w * PAD))
    y1 = max(0, int(cy - h / 2 - h * PAD))
    x2 = min(W, int(cx + w / 2 + w * PAD))
    y2 = min(H, int(cy + h / 2 + h * PAD))
    return img.crop((x1, y1, x2, y2)) if (x2 > x1 and y2 > y1) else img


if MERGED.exists():
    shutil.rmtree(MERGED)
shutil.copytree(BASE, MERGED)

added = 0
for cls_dir in sorted(WEBCAM.iterdir()):
    if not cls_dir.is_dir():
        continue
    dst = MERGED / "train" / cls_dir.name
    dst.mkdir(parents=True, exist_ok=True)
    n = 0
    for p in cls_dir.glob("*.jpg"):
        raw = p.read_bytes()
        img = Image.open(io.BytesIO(raw)).convert("RGB")
        crop_hand(raw, img).save(dst / f"webcam_{p.name}")
        n += 1
        added += 1
    print(f"{cls_dir.name}: +{n} webcam (cropped)")

print(f"\nTotal webcam images added to train: {added}")
for split in ("train", "val", "test"):
    total = sum(1 for _ in (MERGED / split).rglob("*.jpg"))
    print(f"  merged {split}: {total} images")
