"""FastAPI application factory for the MediaPipe AI service.

Wires the app: config, logging, middleware, exception handling, security, the
MediaPipe hand-landmark provider (lifecycle), the classification service, and the
HTTP routes (/health, /landmarks, /classify).
"""

import time
from contextlib import asynccontextmanager

from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse

from app.api.router import api_router
from app.application.mudra_classifier.service import ClassificationService
from app.core.config import SERVICE_NAME, SERVICE_VERSION, get_settings
from app.core.exceptions import AIServiceError
from app.core.logging import configure_logging, get_logger
from app.domain.hand_landmarks.provider import HandLandmarkProvider
from app.domain.hand_landmarks.service import HandLandmarkService
from app.infrastructure.classifiers.factory import create_classifier
from app.infrastructure.providers.mediapipe.provider import MediaPipeHandLandmarkProvider
from app.middleware.correlation_id import CorrelationIdMiddleware, get_correlation_id

logger = get_logger(__name__)


def create_app(provider: HandLandmarkProvider | None = None) -> FastAPI:
    """Build the FastAPI app. A provider may be injected (used by tests)."""
    settings = get_settings()
    configure_logging(settings.log_level)

    @asynccontextmanager
    async def lifespan(app: FastAPI):
        app.state.started_at = time.monotonic()
        landmark_provider = provider or MediaPipeHandLandmarkProvider(settings)
        service = HandLandmarkService(landmark_provider)
        try:
            service.initialize()
        except Exception:
            # Start in a degraded state so /health can report the problem
            # instead of the process crash-looping.
            logger.exception("Hand-landmark provider failed to initialize (degraded mode)")
        app.state.hand_landmark_service = service
        app.state.classification_service = ClassificationService(
            create_classifier(settings.mudra_classifier_driver)
        )
        try:
            yield
        finally:
            service.shutdown()

    app = FastAPI(title=SERVICE_NAME, version=SERVICE_VERSION, lifespan=lifespan)
    app.add_middleware(CorrelationIdMiddleware)

    @app.exception_handler(AIServiceError)
    async def _handle_ai_service_error(_: Request, exc: AIServiceError) -> JSONResponse:
        return JSONResponse(
            status_code=exc.status_code,
            content={
                "error": {"code": exc.code, "message": exc.message},
                "correlation_id": get_correlation_id(),
            },
        )

    app.include_router(api_router)
    return app


app = create_app()
