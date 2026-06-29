"""Loads the MediaPipe HandLandmarker model from disk.

MediaPipe is imported lazily inside `load()` so that importing this module (and
the rest of the app) does not require MediaPipe to be installed — unit tests use
a fake provider and never trigger this path.
"""

from pathlib import Path

from app.core.config import Settings
from app.core.exceptions import ModelNotLoadedError


class ModelLoader:
    @staticmethod
    def load(settings: Settings):
        model_path = Path(settings.model_path)
        if not model_path.exists():
            raise ModelNotLoadedError(f"Hand-landmark model not found at '{model_path}'.")

        from mediapipe.tasks import python as mp_python
        from mediapipe.tasks.python import vision

        from app.infrastructure.providers.mediapipe._native import suppress_native_stderr

        base_options = mp_python.BaseOptions(model_asset_path=str(model_path))
        options = vision.HandLandmarkerOptions(
            base_options=base_options,
            running_mode=vision.RunningMode.IMAGE,
            num_hands=settings.max_hands,
            min_hand_detection_confidence=settings.detection_confidence,
            min_hand_presence_confidence=settings.detection_confidence,
            min_tracking_confidence=settings.tracking_confidence,
        )
        with suppress_native_stderr():
            return vision.HandLandmarker.create_from_options(options)
