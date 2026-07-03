"""Capture webcam images of your hand for one mudra, to fix domain shift.

The dataset-trained model doesn't generalize to your camera/lighting, so we
collect real samples from THIS webcam and retrain on them.

Usage (from ai-service/, with the practice browser tab CLOSED so the camera is free):
  python training/capture_webcam.py shikhar
  python training/capture_webcam.py pataka
  ... one run per mudra: shikhar, pataka, soochi, trishool, mayur, shuktund

Controls: SPACE = save a frame, Q or ESC = quit.
Aim for ~20 shots per mudra, varying angle/distance/lighting a little.
"""

import sys
from pathlib import Path

import cv2

ROOT = Path(__file__).resolve().parents[1]
mudra = sys.argv[1] if len(sys.argv) > 1 else "unknown"
out = ROOT / "datasets" / "webcam" / mudra
out.mkdir(parents=True, exist_ok=True)

start = len(list(out.glob("*.jpg")))
cap = cv2.VideoCapture(0)
if not cap.isOpened():
    print("ERROR: cannot open the webcam. Close the browser practice tab first.")
    sys.exit(1)

print(f"Capturing '{mudra}'. SPACE = save, Q/ESC = quit. Vary angle & distance a little.")
i = start
while True:
    ok, frame = cap.read()
    if not ok:
        break
    disp = frame.copy()
    cv2.putText(
        disp, f"{mudra}: {i - start} saved   [SPACE=save  Q=quit]",
        (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2,
    )
    cv2.imshow("capture", disp)
    key = cv2.waitKey(1) & 0xFF
    if key == ord(" "):
        cv2.imwrite(str(out / f"{mudra}_{i:03d}.jpg"), frame)
        i += 1
    elif key in (ord("q"), 27):
        break

cap.release()
cv2.destroyAllWindows()
print(f"Saved {i - start} new images -> {out}")
