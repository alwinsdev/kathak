"""Per-class precision/recall/F1 for the trained classifier on val+test splits."""

import sys
from collections import Counter
from pathlib import Path

from ultralytics import YOLO

ROOT = Path(__file__).resolve().parents[1]
# args: [cls_dataset_dir] [model_path]
DS = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / "datasets" / "kathak_cls"
model_path = sys.argv[2] if len(sys.argv) > 2 else str(ROOT / "runs" / "kathak_cls" / "weights" / "best.pt")
model = YOLO(model_path)
names = model.names

tp, fp, fn = Counter(), Counter(), Counter()
total = correct = 0
IMG_EXT = {".jpg", ".jpeg", ".png", ".bmp", ".webp"}

for split in ("val", "test"):
    for cls_dir in (DS / split).iterdir():
        if not cls_dir.is_dir():
            continue
        true = cls_dir.name
        for img in cls_dir.iterdir():
            if img.suffix.lower() not in IMG_EXT:
                continue
            r = model.predict(str(img), verbose=False)[0]
            pred = names[int(r.probs.top1)]
            total += 1
            if pred == true:
                correct += 1
                tp[true] += 1
            else:
                fp[pred] += 1
                fn[true] += 1

print(f"\nImages evaluated (val+test): {total}   overall top-1 accuracy: {correct / total:.1%}\n")
print(f"{'class':16s} {'prec':>6s} {'recall':>7s} {'f1':>6s} {'support':>8s}")
f1s = []
for c in sorted(names.values()):
    support = tp[c] + fn[c]
    if support == 0:
        continue
    prec = tp[c] / (tp[c] + fp[c]) if (tp[c] + fp[c]) else 0.0
    rec = tp[c] / support if support else 0.0
    f1 = 2 * prec * rec / (prec + rec) if (prec + rec) else 0.0
    f1s.append(f1)
    flag = "  <- reliable" if (rec >= 0.7 and prec >= 0.5 and support >= 2) else ""
    print(f"{c:16s} {prec:6.2f} {rec:7.2f} {f1:6.2f} {support:8d}{flag}")
print(f"\nmacro-F1: {sum(f1s) / len(f1s):.2f}")
