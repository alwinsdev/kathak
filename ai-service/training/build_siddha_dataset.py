"""Build the Siddha classification dataset from webcam captures.

Auto-discovers every class folder under datasets/webcam/ (e.g. aakash, other —
future mudras just add folders), crops each frame to the hand via the MediaPipe
detector on :8002 (matching the crop-at-inference pipeline), and writes an
80/20 train/val split to datasets/siddha_cls/.

Run (from ai-service/):
  python training/build_siddha_dataset.py
"""

import io
import random
import shutil
from pathlib import Path

import requests
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "datasets" / "webcam"
DST = ROOT / "datasets" / "siddha_cls"
DETECT = "http://localhost:8002"
PAD = 0.35
VAL_FRACTION = 0.2
SEED = 42


def crop_hand(raw: bytes, img: Image.Image) -> tuple[Image.Image, bool]:
    """Crop to the detected hand; fall back to the full frame if none found."""
    try:
        response = requests.post(
            f"{DETECT}/landmarks",
            headers={"X-API-Key": "change-me"},
            files={"image": ("f.jpg", raw, "image/jpeg")},
            timeout=5,
        )
        data = response.json()
        hands = data.get("hands") or []
    except Exception:
        hands = []
    if not hands:
        return img, False
    box = hands[0]["bbox"]
    width, height = data["image_width"], data["image_height"]
    cx, cy, w, h = box["cx"], box["cy"], box["width"], box["height"]
    x1 = max(0, int(cx - w / 2 - w * PAD))
    y1 = max(0, int(cy - h / 2 - h * PAD))
    x2 = min(width, int(cx + w / 2 + w * PAD))
    y2 = min(height, int(cy + h / 2 + h * PAD))
    if x2 <= x1 or y2 <= y1:
        return img, False
    return img.crop((x1, y1, x2, y2)), True


if DST.exists():
    shutil.rmtree(DST)

rng = random.Random(SEED)
total_cropped = total_fallback = 0

for cls_dir in sorted(p for p in SRC.iterdir() if p.is_dir()):
    images = sorted(cls_dir.glob("*.jpg"))
    if not images:
        continue
    rng.shuffle(images)
    val_count = max(1, round(len(images) * VAL_FRACTION))
    splits = {"val": images[:val_count], "train": images[val_count:]}

    for split, files in splits.items():
        out = DST / split / cls_dir.name
        out.mkdir(parents=True, exist_ok=True)
        for path in files:
            raw = path.read_bytes()
            img = Image.open(io.BytesIO(raw)).convert("RGB")
            cropped, ok = crop_hand(raw, img)
            cropped.save(out / path.name)
            total_cropped += int(ok)
            total_fallback += int(not ok)
    print(f"{cls_dir.name}: train={len(splits['train'])} val={len(splits['val'])}")

print(f"\nCropped: {total_cropped} · full-frame fallbacks: {total_fallback}")
for split in ("train", "val"):
    count = sum(1 for _ in (DST / split).rglob("*.jpg"))
    print(f"  {split}: {count} images")
