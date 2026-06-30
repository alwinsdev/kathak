"""Test fixtures.

Uses a fake provider so the suite runs without MediaPipe or a model file.
The API key is set before importing the app so the cached settings pick it up.
"""

import os

os.environ.setdefault("API_KEY", "test-key")
API_KEY = os.environ["API_KEY"]

import pytest  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402

from app.domain.hand_landmarks.models import (  # noqa: E402
    BoundingBox,
    HandDetections,
    HandLandmarks,
    Landmark,
)
from app.domain.hand_landmarks.provider import HandLandmarkProvider  # noqa: E402
from app.main import create_app  # noqa: E402


def make_hand(
    handedness: str = "Right", score: float = 0.99, landmark_count: int = 21
) -> HandLandmarks:
    """Build a synthetic hand for tests (21 landmarks by default)."""
    landmarks = [Landmark(x=0.5, y=0.5, z=0.0) for _ in range(landmark_count)]
    return HandLandmarks(
        handedness=handedness,
        score=score,
        bbox=BoundingBox(cx=320.0, cy=240.0, width=100.0, height=120.0),
        landmarks=landmarks,
    )


class FakeHandLandmarkProvider(HandLandmarkProvider):
    def __init__(self, *, ready: bool = True, hands: list[HandLandmarks] | None = None) -> None:
        self._ready = ready
        self._loaded = False
        self._hands = hands if hands is not None else []

    def detect(self, image_bytes: bytes) -> HandDetections:  # noqa: ARG002 - fake ignores input
        return HandDetections(hands=self._hands, image_width=640, image_height=480)

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


def build_client(*, ready: bool = True, hands: list[HandLandmarks] | None = None) -> TestClient:
    app = create_app(provider=FakeHandLandmarkProvider(ready=ready, hands=hands))
    return TestClient(app)


@pytest.fixture
def client() -> TestClient:
    with build_client() as test_client:
        yield test_client


@pytest.fixture
def degraded_client() -> TestClient:
    with build_client(ready=False) as test_client:
        yield test_client
