"""Train YOLOv8n-cls on the kathak mudra classification dataset.

Run from anywhere with the conda env that has ultralytics:
  python ai-service/training/train_classify.py
"""

import sys
from pathlib import Path

from ultralytics import YOLO

ROOT = Path(__file__).resolve().parents[1]  # ai-service/

# args: [cls_dataset_dir] [model] [epochs] [run_name]
data = sys.argv[1] if len(sys.argv) > 1 else str(ROOT / "datasets" / "kathak_cls")
model_name = sys.argv[2] if len(sys.argv) > 2 else "yolov8n-cls.pt"
epochs = int(sys.argv[3]) if len(sys.argv) > 3 else 30
run_name = sys.argv[4] if len(sys.argv) > 4 else "kathak_cls"

model = YOLO(model_name)  # pretrained classifier (auto-downloads)
model.train(
    data=data,
    epochs=epochs,
    imgsz=224,
    batch=32,
    device="cpu",
    project=str(ROOT / "runs"),
    name=run_name,
    exist_ok=True,
    plots=True,  # confusion matrix + results curves
)
