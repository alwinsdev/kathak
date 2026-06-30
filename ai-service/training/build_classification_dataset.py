"""Convert the YOLO detection/seg dataset into a classification folder tree.

Each source image has exactly one mudra (verified), so the image's class is the
first token of its label file. Produces:

  datasets/kathak_cls/{train,val,test}/<class_name>/<image>

Run (from ai-service/):
  docker run --rm -v "$PWD:/ws" -w /ws python:3.11-slim sh -c \
    "pip install -q pyyaml && python training/build_classification_dataset.py"
"""

import shutil
import sys
from pathlib import Path

import yaml

SRC = Path(sys.argv[1] if len(sys.argv) > 1 else "datasets/kathak")
DST = Path(sys.argv[2] if len(sys.argv) > 2 else "datasets/kathak_cls")
SPLIT_MAP = {"train": "train", "valid": "val", "test": "test"}

names = yaml.safe_load((SRC / "data.yaml").read_text())["names"]

if DST.exists():
    shutil.rmtree(DST)

copied, skipped = 0, 0
for src_split, dst_split in SPLIT_MAP.items():
    img_dir, lbl_dir = SRC / src_split / "images", SRC / src_split / "labels"
    if not img_dir.is_dir():
        continue
    for img in img_dir.iterdir():
        lbl = lbl_dir / (img.stem + ".txt")
        lines = [ln for ln in lbl.read_text().splitlines() if ln.strip()] if lbl.exists() else []
        if not lines:
            skipped += 1
            continue
        class_id = int(lines[0].split()[0])
        out_dir = DST / dst_split / names[class_id]
        out_dir.mkdir(parents=True, exist_ok=True)
        shutil.copy2(img, out_dir / img.name)
        copied += 1

print(f"Converted {copied} images ({skipped} skipped with empty labels).")
for dst_split in ("train", "val", "test"):
    d = DST / dst_split
    n_cls = len(list(d.iterdir())) if d.exists() else 0
    n_img = sum(1 for _ in d.rglob("*.*")) if d.exists() else 0
    print(f"  {dst_split}: classes={n_cls} images={n_img}")
