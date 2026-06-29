"""Domain/service exceptions mapped to consistent HTTP error responses."""


class AIServiceError(Exception):
    """Base error. Carries an HTTP status code and a machine-readable code."""

    status_code: int = 500
    code: str = "internal_error"

    def __init__(
        self,
        message: str = "Internal server error.",
        *,
        status_code: int | None = None,
        code: str | None = None,
    ) -> None:
        super().__init__(message)
        self.message = message
        if status_code is not None:
            self.status_code = status_code
        if code is not None:
            self.code = code


class ModelNotLoadedError(AIServiceError):
    """The hand-landmark model is not available / not initialized."""

    status_code = 503
    code = "model_not_loaded"


class UnauthorizedError(AIServiceError):
    """Missing or invalid API key."""

    status_code = 401
    code = "unauthorized"
