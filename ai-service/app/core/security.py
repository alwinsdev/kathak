"""API-key authentication scaffold.

Provided now for the protected routes added in later phases (e.g. the
prediction endpoint). The public `/health` route intentionally does not use it.
"""

from fastapi import Header

from app.core.config import get_settings
from app.core.exceptions import UnauthorizedError


async def require_api_key(x_api_key: str | None = Header(default=None)) -> None:
    settings = get_settings()
    if not settings.api_key or x_api_key != settings.api_key:
        raise UnauthorizedError("Invalid or missing API key.")
