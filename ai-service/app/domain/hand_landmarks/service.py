"""Domain service for the hand-landmark capability.

Named for the business capability (hand landmarks), not the implementation.
It orchestrates a `HandLandmarkProvider`; the concrete engine (MediaPipe today)
lives in the infrastructure layer and can change without renaming this service.
"""

from app.core.logging import get_logger
from app.domain.hand_landmarks.provider import HandLandmarkProvider

logger = get_logger(__name__)


class HandLandmarkService:
    def __init__(self, provider: HandLandmarkProvider) -> None:
        self._provider = provider
        self._initialized = False

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
