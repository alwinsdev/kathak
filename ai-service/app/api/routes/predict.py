"""Prediction endpoint (protected): a frame -> detected hand landmarks.

Thin: validates the upload, delegates perception to the domain service, maps the
result to the response schema, and logs a structured (coordinate-free) line.
"""

from fastapi import APIRouter, Depends, File, Request, UploadFile

from app.core.config import get_settings
from app.core.exceptions import InvalidImageError, ModelNotLoadedError, PayloadTooLargeError
from app.core.logging import get_logger
from app.core.security import require_api_key
from app.middleware.correlation_id import get_correlation_id
from app.schemas.prediction import PredictionResponse

router = APIRouter(tags=["prediction"])
logger = get_logger(__name__)


@router.post("/predict", response_model=PredictionResponse, dependencies=[Depends(require_api_key)])
async def predict(request: Request, image: UploadFile = File(...)) -> PredictionResponse:
    settings = get_settings()

    image_bytes = await image.read()
    if not image_bytes:
        raise InvalidImageError("Empty image payload.")
    if len(image_bytes) > settings.max_image_mb * 1024 * 1024:
        raise PayloadTooLargeError(f"Image exceeds the maximum of {settings.max_image_mb} MB.")

    service = request.app.state.hand_landmark_service
    if not service.is_ready():
        raise ModelNotLoadedError("Hand-landmark model is not ready.")

    result = service.detect(image_bytes, correlation_id=get_correlation_id())

    logger.info(
        "prediction",
        extra={
            "processing_time_ms": result.processing_time_ms,
            "image_width": result.image_width,
            "image_height": result.image_height,
            "hands_detected": len(result.hands),
            "request_status": "success",
        },
    )

    return PredictionResponse.from_domain(result)
