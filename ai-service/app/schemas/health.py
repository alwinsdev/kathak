"""Health endpoint response schema."""

from pydantic import BaseModel


class HealthResponse(BaseModel):
    status: str
    service: str
    version: str
    model_loaded: bool
    mediapipe_version: str
    python_version: str
    environment: str
    uptime: str
