"""Correlation-ID propagation middleware.

Reads `X-Correlation-ID` from the incoming request (or generates one), exposes
it via a context variable for structured logs, and echoes it on the response —
matching the correlation IDs emitted by the Laravel side.
"""

import uuid
from contextvars import ContextVar

from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request

HEADER_NAME = "X-Correlation-ID"

_correlation_id: ContextVar[str | None] = ContextVar("correlation_id", default=None)


def get_correlation_id() -> str | None:
    return _correlation_id.get()


class CorrelationIdMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        correlation_id = request.headers.get(HEADER_NAME) or str(uuid.uuid4())
        token = _correlation_id.set(correlation_id)
        try:
            response = await call_next(request)
        finally:
            _correlation_id.reset(token)
        response.headers[HEADER_NAME] = correlation_id
        return response
