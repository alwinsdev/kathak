"""Classification endpoint (protected): a frame -> recognized hand shape.

Composes the two existing capabilities — landmark detection then classification —
into a single call the Laravel app can use in place of Roboflow. It detects the
hand, classifies the first one found, and returns a small, extensible contract.
Perception coordinates are never logged.
"""

import time

from fastapi import APIRouter, Depends, File, Request, UploadFile

from app.core.config import get_settings
from app.core.exceptions import InvalidImageError, ModelNotLoadedError, PayloadTooLargeError
from app.core.logging import get_logger
from app.core.security import require_api_key
from app.domain.mudra_classifier.request import ClassificationRequest
from app.middleware.correlation_id import get_correlation_id
from app.schemas.classification import ClassifyResponse, PredictionSchema

router = APIRouter(tags=["classification"])
logger = get_logger(__name__)


@router.post("/classify", response_model=ClassifyResponse, dependencies=[Depends(require_api_key)])
async def classify(request: Request, image: UploadFile = File(...)) -> ClassifyResponse:
    settings = get_settings()

    image_bytes = await image.read()
    if not image_bytes:
        raise InvalidImageError("Empty image payload.")
    if len(image_bytes) > settings.max_image_mb * 1024 * 1024:
        raise PayloadTooLargeError(f"Image exceeds the maximum of {settings.max_image_mb} MB.")

    landmark_service = request.app.state.hand_landmark_service
    if not landmark_service.is_ready():
        raise ModelNotLoadedError("Hand-landmark model is not ready.")

    correlation_id = get_correlation_id()
    started = time.perf_counter()
    detection = landmark_service.detect(image_bytes, correlation_id=correlation_id)

    prediction = None
    if detection.hands:
        result = request.app.state.classification_service.classify(
            ClassificationRequest(hand=detection.hands[0], correlation_id=correlation_id)
        )
        prediction = PredictionSchema(label=result.label, confidence=result.confidence)
    processing_time_ms = int((time.perf_counter() - started) * 1000)

    logger.info(
        "classification",
        extra={
            "processing_time_ms": processing_time_ms,
            "hands_detected": len(detection.hands),
            "label": prediction.label if prediction else None,
            "request_status": "success",
        },
    )

    return ClassifyResponse(
        success=True,
        prediction=prediction,
        hands_detected=len(detection.hands),
        processing_time_ms=processing_time_ms,
    )
