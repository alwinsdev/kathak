# YOLO Mudra Model — Training & Merge Runbook

All commands are **PowerShell**, run from:

```powershell
cd C:\xampp\htdocs\kathak\ai-service
```

Python is the conda `base` env that has `ultralytics` + `torch`. To keep commands
short, set this once per terminal:

```powershell
$py = "C:\Users\iamal\miniconda3\python.exe"
```

Paths used below:

| Thing | Path |
|---|---|
| Base dataset (Roboflow v7) | `datasets/kathak` |
| Classification dataset (all 26) | `datasets/kathak_cls` |
| 6-class subset | `datasets/kathak_sub_cls` |
| Webcam captures (raw) | `datasets/webcam/<mudra>` |
| Merged dataset (subset + webcam) | `datasets/kathak_merged_cls` |
| Training outputs | `runs/<name>/weights/best.pt` |
| Deployed model | `models/kathak.pt` |
| Classifier server | `yolo_server.py` (port 8001) |
| Hand detector | Docker `mediapipe-ai:dev` (port 8002) |

> `datasets/`, `runs/`, and `*.pt` are git-ignored.

---

## 0. Start the MediaPipe hand detector (port 8002) — local Python, no Docker

Needed both to **crop webcam captures** and to **serve** predictions.
Runs in the dedicated `mp` conda env (Python 3.11, where mediapipe has wheels).

```powershell
cd C:\xampp\htdocs\kathak\ai-service
$env:API_KEY='change-me'; $env:DETECTION_CONFIDENCE='0.3'
# 127.0.0.1: internal service — must never be reachable from the network
& C:\Users\iamal\miniconda3\envs\mp\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8002
# check (new terminal):
curl.exe -s http://localhost:8002/health
```

> One-time setup (already done): `conda create -y -n mp python=3.11`, then
> `pip install fastapi "uvicorn[standard]" pydantic pydantic-settings python-multipart "mediapipe>=0.10.14,<0.11" pillow "numpy>=1.26,<2"`,
> and `models/hand_landmarker.task` downloaded from the MediaPipe model zoo.

---

## THE COMMON LOOP (retrain from new webcam captures)

This is what you run each time to improve the model with more of your own images.

### 1. Capture webcam images — one run per mudra

Close the browser practice tab first (frees the camera). In the preview window,
press **SPACE** ~20 times per mudra (vary angle / distance / lighting), then **Q**.

```powershell
& $py training/capture_webcam.py shikhar
& $py training/capture_webcam.py pataka
& $py training/capture_webcam.py soochi
& $py training/capture_webcam.py trishool
& $py training/capture_webcam.py mayur
& $py training/capture_webcam.py shuktund
```

Images are appended to `datasets/webcam/<mudra>/` (re-running adds more).

### 2. Merge — crop each webcam frame to the hand + add to the dataset

(The MediaPipe detector on :8002 must be running — see step 0.)

```powershell
& $py training/merge_webcam.py
```

Produces `datasets/kathak_merged_cls/` (subset + cropped webcam in `train/`).

### 3. Train

```powershell
# args: <cls_dataset_dir> <pretrained_model> <epochs> <run_name>
& $py training/train_classify.py datasets/kathak_merged_cls yolov8n-cls.pt 100 kathak_merged_cls
```

Best weights: `runs/kathak_merged_cls/weights/best.pt`
Artifacts: `runs/kathak_merged_cls/` (confusion_matrix.png, results.png, ...).

### 4. Evaluate (per-class precision / recall / F1 on val+test)

```powershell
# args: <cls_dataset_dir> <model_path>
& $py training/eval_classify.py datasets/kathak_merged_cls runs/kathak_merged_cls/weights/best.pt
```

### 5. Deploy — swap the model + restart the server

```powershell
Copy-Item runs/kathak_merged_cls/weights/best.pt models/kathak.pt -Force

# stop the running server (Ctrl+C in its window), then start it again:
& $py yolo_server.py
```

The server reloads `models/kathak.pt` on start. Verify:

```powershell
curl.exe -s http://localhost:8001/health
```

---

## Serve (run the live inference server, port 8001)

```powershell
& $py yolo_server.py
```

Pipeline: `frame -> MediaPipe hand-crop (:8002) -> YOLO classify -> {label, confidence}`.
Laravel calls this via `MEDIAPIPE_URL=http://localhost:8001` (`INFERENCE_DRIVER=mediapipe`).

---

## ONE-TIME dataset prep (already done — for reference / a fresh dataset)

Only needed when starting from a new Roboflow export in `datasets/kathak/`.

```powershell
# 1. Inspect the dataset (type, classes, counts, label quality)
& $py training/inspect_dataset.py datasets/kathak

# 2. Convert detection/seg labels -> classification folders (all 26 classes)
& $py training/build_classification_dataset.py datasets/kathak datasets/kathak_cls

# 3. Build the 6-class distinct subset (done manually with cp; classes:
#    shikhar, shuktund, pataka, soochi, mayur, trishool -> datasets/kathak_sub_cls)
```

---

## Quick reference — one-line retrain

After capturing more images (step 1):

```powershell
& $py training/merge_webcam.py; `
& $py training/train_classify.py datasets/kathak_merged_cls yolov8n-cls.pt 100 kathak_merged_cls; `
& $py training/eval_classify.py datasets/kathak_merged_cls runs/kathak_merged_cls/weights/best.pt; `
Copy-Item runs/kathak_merged_cls/weights/best.pt models/kathak.pt -Force
# then restart: & $py yolo_server.py
```

---

## Notes

- **More images = better.** Aim for ~20 webcam shots per mudra; 2–5 barely moves accuracy.
- **CPU training** (`device=cpu` in `train_classify.py`) — ~10–15 min for 100 epochs on this data. Fine for a POC.
- **Both services must run** for live detection: detector (:8002) + classifier (:8001).
- Classification produces **confusion matrix + accuracy/results**, not PR/F1 curves (those are detection-only).
