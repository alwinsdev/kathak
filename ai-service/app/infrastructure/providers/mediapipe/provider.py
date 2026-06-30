"""MediaPipe implementation of the HandLandmarkProvider port.

Phase 1: lifecycle only (load / warmup / readiness / shutdown). Landmark
extraction is added in a later phase. MediaPipe/numpy are imported lazily so the
module can be imported without the heavy dependencies present.
"""

from app.core.config import Settings
from app.core.exceptions import ModelNotLoadedError
from app.core.logging import get_logger
from app.domain.hand_landmarks.models import HandDetections
from app.domain.hand_landmarks.provider import HandLandmarkProvider
from app.infrastructure.image.codec import decode_image
from app.infrastructure.providers.mediapipe._native import suppress_native_stderr
from app.infrastructure.providers.mediapipe.mapper import map_hands
from app.infrastructure.providers.mediapipe.model_loader import ModelLoader

logger = get_logger(__name__)


class MediaPipeHandLandmarkProvider(HandLandmarkProvider):
    def __init__(self, settings: Settings) -> None:
        self._settings = settings
        self._landmarker = None

    def detect(self, image_bytes: bytes) -> HandDetections:
        if self._landmarker is None:
            raise ModelNotLoadedError("Hand-landmark model is not initialized.")

        import mediapipe as mp

        rgb_array, width, height = decode_image(image_bytes, self._settings.max_image_dimension)
        mp_image = mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb_array)
        with suppress_native_stderr():
            mp_result = self._landmarker.detect(mp_image)

        return HandDetections(
            hands=map_hands(mp_result, width, height),
            image_width=width,
            image_height=height,
        )

    def load(self) -> None:
        self._landmarker = ModelLoader.load(self._settings)
        logger.info("MediaPipe HandLandmarker loaded from '%s'", self._settings.model_path)

    def warmup(self) -> None:
        if self._landmarker is None:
            return
        import mediapipe as mp
        import numpy as np

        from app.infrastructure.providers.mediapipe._native import suppress_native_stderr

        blank = np.zeros((64, 64, 3), dtype=np.uint8)
        image = mp.Image(image_format=mp.ImageFormat.SRGB, data=blank)
        with suppress_native_stderr():
            self._landmarker.detect(image)
        logger.info("MediaPipe warmup complete")

    def is_ready(self) -> bool:
        return self._landmarker is not None

    def close(self) -> None:
        if self._landmarker is not None:
            self._landmarker.close()
            self._landmarker = None

    @property
    def provider_version(self) -> str:
        try:
            from importlib.metadata import version

            return version("mediapipe")
        except Exception:  # pragma: no cover - diagnostics only
            return "unknown"
