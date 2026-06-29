"""Health / readiness endpoint (public — no API key)."""

import platform
import time

from fastapi import APIRouter, Request
from fastapi.responses import JSONResponse

from app.core.config import SERVICE_NAME, SERVICE_VERSION, get_settings
from app.schemas.health import HealthResponse

router = APIRouter(tags=["health"])


def _format_uptime(started_at: float) -> str:
    seconds = int(time.monotonic() - started_at)
    hours, remainder = divmod(seconds, 3600)
    minutes, secs = divmod(remainder, 60)
    return f"{hours:02d}:{minutes:02d}:{secs:02d}"


@router.get("/health", response_model=HealthResponse)
def health(request: Request) -> JSONResponse:
    settings = get_settings()
    service = request.app.state.hand_landmark_service
    ready = service.is_ready()

    body = HealthResponse(
        status="healthy" if ready else "unhealthy",
        service=SERVICE_NAME,
        version=SERVICE_VERSION,
        model_loaded=ready,
        mediapipe_version=service.provider_version,
        python_version=platform.python_version(),
        environment=settings.environment,
        uptime=_format_uptime(request.app.state.started_at),
    )
    return JSONResponse(status_code=200 if ready else 503, content=body.model_dump())
