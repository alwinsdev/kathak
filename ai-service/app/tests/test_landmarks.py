"""/landmarks endpoint tests (fake provider — no MediaPipe)."""

from app.core.config import LANDMARK_CONTRACT_VERSION
from app.tests.conftest import API_KEY, build_client, make_hand

HEADERS = {"X-API-Key": API_KEY}
SMALL_BYTES = b"x" * 64  # fake provider ignores the content


def _files(content: bytes = SMALL_BYTES):
    return {"image": ("frame.jpg", content, "image/jpeg")}


def test_landmarks_requires_api_key() -> None:
    with build_client() as client:
        response = client.post("/landmarks", files=_files())
    assert response.status_code == 401


def test_landmarks_rejects_invalid_api_key() -> None:
    with build_client() as client:
        response = client.post("/landmarks", files=_files(), headers={"X-API-Key": "wrong"})
    assert response.status_code == 401


def test_landmarks_missing_image_field_returns_422() -> None:
    with build_client() as client:
        response = client.post("/landmarks", headers=HEADERS)
    assert response.status_code == 422


def test_landmarks_empty_image_returns_400() -> None:
    with build_client() as client:
        response = client.post("/landmarks", files=_files(b""), headers=HEADERS)
    assert response.status_code == 400
    assert response.json()["error"]["code"] == "invalid_image"


def test_landmarks_oversize_payload_returns_413() -> None:
    with build_client() as client:
        response = client.post(
            "/landmarks", files=_files(b"\x00" * (6 * 1024 * 1024)), headers=HEADERS
        )
    assert response.status_code == 413
    assert response.json()["error"]["code"] == "payload_too_large"


def test_landmarks_no_hand_returns_empty() -> None:
    with build_client(hands=[]) as client:
        response = client.post("/landmarks", files=_files(), headers=HEADERS)
    assert response.status_code == 200
    body = response.json()
    assert body["api_version"] == LANDMARK_CONTRACT_VERSION
    assert body["hands_detected"] == 0
    assert body["hands"] == []
    assert body["image_width"] == 640
    assert "processing_time_ms" in body and "detected_at" in body


def test_landmarks_single_hand() -> None:
    with build_client(hands=[make_hand("Right")]) as client:
        response = client.post("/landmarks", files=_files(), headers=HEADERS)
    assert response.status_code == 200
    body = response.json()
    assert body["api_version"] == LANDMARK_CONTRACT_VERSION
    assert body["hands_detected"] == 1
    hand = body["hands"][0]
    assert hand["handedness"] == "Right"
    assert len(hand["landmarks"]) == 21
    assert set(hand["bbox"]) == {"cx", "cy", "width", "height"}


def test_landmarks_two_hands() -> None:
    with build_client(hands=[make_hand("Left"), make_hand("Right")]) as client:
        response = client.post("/landmarks", files=_files(), headers=HEADERS)
    assert response.status_code == 200
    body = response.json()
    assert body["hands_detected"] == 2
    assert {h["handedness"] for h in body["hands"]} == {"Left", "Right"}


def test_landmarks_malformed_count_returns_500() -> None:
    # A hand with the wrong number of landmarks must never be returned.
    with build_client(hands=[make_hand("Right", landmark_count=20)]) as client:
        response = client.post("/landmarks", files=_files(), headers=HEADERS)
    assert response.status_code == 500
    assert response.json()["error"]["code"] == "malformed_landmarks"


def test_landmarks_returns_503_when_model_not_ready() -> None:
    with build_client(ready=False) as client:
        response = client.post("/landmarks", files=_files(), headers=HEADERS)
    assert response.status_code == 503
