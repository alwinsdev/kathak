"""Response schema for the /classify endpoint.

Deliberately small but extensible: a top-level ``success`` flag and a nested
``prediction`` object leave room to grow without breaking the Laravel consumer.
``prediction`` is null when no hand was detected.
"""

from pydantic import BaseModel


class PredictionSchema(BaseModel):
    label: str
    confidence: float


class ClassifyResponse(BaseModel):
    success: bool
    prediction: PredictionSchema | None
    hands_detected: int
    processing_time_ms: int
