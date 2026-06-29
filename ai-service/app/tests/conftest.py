"""Test fixtures.

Uses a fake provider so the suite runs without MediaPipe or a model file.
"""

import pytest
from fastapi.testclient import TestClient

from app.domain.hand_landmarks.provider import HandLandmarkProvider
from app.main import create_app


class FakeHandLandmarkProvider(HandLandmarkProvider):
    def __init__(self, *, ready: bool = True) -> None:
        self._ready = ready
        self._loaded = False

    def load(self) -> None:
        self._loaded = True

    def warmup(self) -> None:
        pass

    def is_ready(self) -> bool:
        return self._ready and self._loaded

    def close(self) -> None:
        self._loaded = False

    @property
    def provider_version(self) -> str:
        return "test-1.0"


@pytest.fixture
def client() -> TestClient:
    app = create_app(provider=FakeHandLandmarkProvider(ready=True))
    with TestClient(app) as test_client:
        yield test_client


@pytest.fixture
def degraded_client() -> TestClient:
    app = create_app(provider=FakeHandLandmarkProvider(ready=False))
    with TestClient(app) as test_client:
        yield test_client
