"""Landmark extraction endpoint (protected): a frame -> detected hand landmarks.

At this stage the service only extracts hand landmarks; it does not classify or
recognize a mudra (later phases may add ``/classify`` or ``/recognize``).

Thin: validates the upload, delegates perception to the domain service, maps the
result to the response schema, and logs a structured (coordinate-free) line.
"""

from fastapi import APIRouter, Depends, File, Request, UploadFile

from app.core.config import get_settings
from app.core.exceptions import InvalidImageError, ModelNotLoadedError, PayloadTooLargeError
from app.core.logging import get_logger
from app.core.security import require_api_key
from app.middleware.correlation_id import get_correlation_id
from app.schemas.landmarks import LandmarkResponse

router = APIRouter(tags=["landmarks"])
logger = get_logger(__name__)


@router.post("/landmarks", response_model=LandmarkResponse, dependencies=[Depends(require_api_key)])
async def extract_landmarks(request: Request, image: UploadFile = File(...)) -> LandmarkResponse:
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
        "landmarks",
        extra={
            "processing_time_ms": result.processing_time_ms,
            "image_width": result.image_width,
            "image_height": result.image_height,
            "hands_detected": len(result.hands),
            "request_status": "success",
        },
    )

    return LandmarkResponse.from_domain(result)
