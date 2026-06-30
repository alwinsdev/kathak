"""Inspect a YOLO detection dataset: counts, per-class distribution, label quality.

Run (from ai-service/):
  docker run --rm -v "$PWD:/ws" -w /ws python:3.11-slim sh -c \
    "pip install -q pyyaml pillow && python training/inspect_dataset.py datasets/kathak"
"""

import sys
from collections import Counter
from pathlib import Path

import yaml
from PIL import Image

root = Path(sys.argv[1] if len(sys.argv) > 1 else "datasets/kathak")
data = yaml.safe_load((root / "data.yaml").read_text())
names = data["names"]
nc = data["nc"]
print(f"data.yaml: nc={nc}, names={len(names)} -> {'OK' if nc == len(names) else 'MISMATCH'}")

IMG_EXT = {".jpg", ".jpeg", ".png", ".bmp", ".webp"}
totals = Counter()  # instances per class (all splits)
img_class = Counter()  # images containing class (all splits)
problems = {"bad_lines": 0, "out_of_range": 0, "bad_class": 0, "empty_labels": 0, "corrupt_img": 0, "unpaired": 0}

for split in ("train", "valid", "test"):
    img_dir, lbl_dir = root / split / "images", root / split / "labels"
    if not img_dir.is_dir():
        continue
    imgs = [p for p in img_dir.iterdir() if p.suffix.lower() in IMG_EXT]
    split_inst = Counter()
    for img in imgs:
        lbl = lbl_dir / (img.stem + ".txt")
        if not lbl.exists():
            problems["unpaired"] += 1
            continue
        try:
            Image.open(img).verify()
        except Exception:
            problems["corrupt_img"] += 1
        lines = [ln for ln in lbl.read_text().splitlines() if ln.strip()]
        if not lines:
            problems["empty_labels"] += 1
        seen = set()
        for ln in lines:
            parts = ln.split()
            if len(parts) != 5:
                problems["bad_lines"] += 1
                continue
            try:
                cid = int(parts[0])
                coords = [float(x) for x in parts[1:]]
            except ValueError:
                problems["bad_lines"] += 1
                continue
            if cid < 0 or cid >= nc:
                problems["bad_class"] += 1
                continue
            if any(c < 0 or c > 1 for c in coords):
                problems["out_of_range"] += 1
            split_inst[cid] += 1
            totals[cid] += 1
            seen.add(cid)
        for cid in seen:
            img_class[cid] += 1
    print(f"\n[{split}] images={len(imgs)} instances={sum(split_inst.values())}")

print("\n=== per-class (instances / images-with-class) across all splits ===")
for cid in range(nc):
    bar = "#" * min(40, totals[cid])
    print(f"  {cid:2d} {names[cid]:14s} inst={totals[cid]:4d} imgs={img_class[cid]:4d} {bar}")

zero = [names[c] for c in range(nc) if totals[c] == 0]
weak = [(names[c], totals[c]) for c in range(nc) if 0 < totals[c] < 10]
print(f"\nTotal instances: {sum(totals.values())}")
print(f"Classes with 0 instances: {zero or 'none'}")
print(f"Classes with <10 instances: {weak or 'none'}")
print(f"Label/image problems: {problems}")
