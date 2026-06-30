# Training the mudra detector (YOLOv8n)

Self-hosted recognition uses a YOLOv8 model trained on the kathak-trainer dataset.
Datasets and weights are **git-ignored** (large / user-supplied).

## 1. Download the dataset (you)

On the Roboflow dataset page → pick a version (the largest, e.g. **"Final Dataset
Model"**) → **Download Dataset** → format **YOLOv8** → *Download zip to computer*.

Unzip it so the structure is exactly:

```
ai-service/datasets/kathak/
├── data.yaml          # class names + train/val/test paths
├── train/{images,labels}
├── valid/{images,labels}
└── test/{images,labels}
```

`data.yaml`'s class `names` must be the mudra tokens that match
`mudras.ai_class_label` in Laravel (pataka, shikhar, shuktund, …).

## 2. Train (GPU, RTX 3050)

Run from `ai-service/` (Docker provides CUDA + ultralytics; uses the laptop GPU):

```bash
docker run --rm --gpus all -v "$PWD:/ws" -w /ws ultralytics/ultralytics \
  yolo detect train model=yolov8n.pt data=datasets/kathak/data.yaml \
  imgsz=640 epochs=60 batch=16 device=0 project=runs name=kathak
```

CPU fallback (slow): drop `--gpus all` and set `device=cpu batch=8 epochs=40`.

Best weights land in `runs/kathak/weights/best.pt`. Copy them into place:

```bash
cp runs/kathak/weights/best.pt models/kathak.pt
```

## 3. Serve

The AI service loads `models/kathak.pt` and serves predictions through the same
`/classify` endpoint (set `MUDRA_CLASSIFIER_DRIVER=yolo`). No Laravel change.
