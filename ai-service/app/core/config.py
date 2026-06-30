"""Application configuration (loaded from environment / .env)."""

from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict

SERVICE_NAME = "mediapipe-ai"
SERVICE_VERSION = "1.0.0"
API_VERSION = "v1"

# Version of the landmark response contract (returned as `api_version`). Bumped
# only on a breaking change to the response shape; additive fields keep "1.0".
LANDMARK_CONTRACT_VERSION = "1.0"


class Settings(BaseSettings):
    """Typed application settings.

    Values are defined now (including ones unused in Phase 1) so configuration
    stays stable across later phases. `protected_namespaces=()` silences the
    pydantic warning about the `model_*` field names.
    """

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
        protected_namespaces=(),
    )

    # Runtime
    environment: str = "development"
    host: str = "0.0.0.0"
    port: int = 8001
    log_level: str = "INFO"

    # Security / networking (used by protected routes in later phases)
    api_key: str = ""
    request_timeout: int = 5

    # Hand-landmark provider (MediaPipe) — placeholders for later phases
    model_path: str = "models/hand_landmarker.task"
    max_hands: int = 1
    detection_confidence: float = 0.5
    tracking_confidence: float = 0.5

    # Classifier provider selection (single composition point: classifiers.factory).
    # Today: stub | rule_based. Future: ml | tensorflow | onnx | pytorch.
    classifier_driver: str = "rule_based"

    # Image input limits (prediction endpoint)
    max_image_mb: int = 5
    max_image_dimension: int = 4096

    @property
    def is_production(self) -> bool:
        return self.environment.lower() == "production"


@lru_cache
def get_settings() -> Settings:
    """Cached settings accessor (single instance per process)."""
    return Settings()
