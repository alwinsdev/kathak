"""Prediction endpoint response schema (frozen contract as of Phase 2).

Mirrors the domain ``DetectionResult``. Later phases may add fields but must not
break this shape — it is the contract consumed by Laravel.
"""

from datetime import datetime

from pydantic import BaseModel, Field

from app.domain.hand_landmarks.models import DetectionResult


class LandmarkSchema(BaseModel):
    x: float
    y: float
    z: float


class BoundingBoxSchema(BaseModel):
    cx: float
    cy: float
    width: float
    height: float


class HandSchema(BaseModel):
    handedness: str
    score: float
    bbox: BoundingBoxSchema = Field(
        description="Approximate, center-based pixel box derived from landmark "
        "extents — not a native object-detection box."
    )
    landmarks: list[LandmarkSchema]


class PredictionResponse(BaseModel):
    hands: list[HandSchema]
    hands_detected: int
    image_width: int
    image_height: int
    processing_time_ms: int
    detected_at: datetime
    correlation_id: str | None

    @classmethod
    def from_domain(cls, result: DetectionResult) -> "PredictionResponse":
        return cls(
            hands=[
                HandSchema(
                    handedness=hand.handedness,
                    score=hand.score,
                    bbox=BoundingBoxSchema(
                        cx=hand.bbox.cx,
                        cy=hand.bbox.cy,
                        width=hand.bbox.width,
                        height=hand.bbox.height,
                    ),
                    landmarks=[LandmarkSchema(x=lm.x, y=lm.y, z=lm.z) for lm in hand.landmarks],
                )
                for hand in result.hands
            ],
            hands_detected=len(result.hands),
            image_width=result.image_width,
            image_height=result.image_height,
            processing_time_ms=result.processing_time_ms,
            detected_at=result.detected_at,
            correlation_id=result.correlation_id,
        )
