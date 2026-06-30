"""Domain service for the hand-landmark capability.

Named for the business capability (hand landmarks), not the implementation.
It orchestrates a `HandLandmarkProvider`; the concrete engine (MediaPipe today)
lives in the infrastructure layer and can change without renaming this service.
"""

from datetime import UTC, datetime
from time import perf_counter

from app.core.logging import get_logger
from app.domain.hand_landmarks.models import DetectionResult
from app.domain.hand_landmarks.provider import HandLandmarkProvider

logger = get_logger(__name__)


class HandLandmarkService:
    def __init__(self, provider: HandLandmarkProvider) -> None:
        self._provider = provider
        self._initialized = False

    def detect(self, image_bytes: bytes, correlation_id: str | None = None) -> DetectionResult:
        """Run hand detection and wrap the perception in a DetectionResult."""
        started = perf_counter()
        detections = self._provider.detect(image_bytes)
        processing_time_ms = int((perf_counter() - started) * 1000)

        return DetectionResult(
            hands=detections.hands,
            image_width=detections.image_width,
            image_height=detections.image_height,
            processing_time_ms=processing_time_ms,
            detected_at=datetime.now(UTC),
            correlation_id=correlation_id,
        )

    def initialize(self) -> None:
        logger.info("Initializing hand-landmark provider")
        self._provider.load()
        self._provider.warmup()
        self._initialized = self._provider.is_ready()
        logger.info("Hand-landmark provider initialized (ready=%s)", self._initialized)

    def is_ready(self) -> bool:
        return self._initialized and self._provider.is_ready()

    def shutdown(self) -> None:
        logger.info("Shutting down hand-landmark provider")
        self._provider.close()
        self._initialized = False

    @property
    def provider_version(self) -> str:
        return self._provider.provider_version
