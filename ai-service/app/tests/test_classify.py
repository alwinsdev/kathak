"""/classify endpoint tests (fake landmark provider; real rule-based classifier)."""

from app.tests.conftest import API_KEY, build_client, hand_from_points, load_landmark_fixture

HEADERS = {"X-API-Key": API_KEY}
SMALL_BYTES = b"x" * 64  # fake provider ignores the content


def _files(content: bytes = SMALL_BYTES):
    return {"image": ("frame.jpg", content, "image/jpeg")}


def _open_hand():
    return hand_from_points(load_landmark_fixture("open_hand.json")["landmarks"])


def test_classify_requires_api_key() -> None:
    with build_client() as client:
        response = client.post("/classify", files=_files())
    assert response.status_code == 401


def test_classify_empty_image_returns_400() -> None:
    with build_client() as client:
        response = client.post("/classify", files=_files(b""), headers=HEADERS)
    assert response.status_code == 400


def test_classify_no_hand_returns_null_prediction() -> None:
    with build_client(hands=[]) as client:
        response = client.post("/classify", files=_files(), headers=HEADERS)
    assert response.status_code == 200
    body = response.json()
    assert body["success"] is True
    assert body["prediction"] is None
    assert body["hands_detected"] == 0
    assert "processing_time_ms" in body


def test_classify_open_hand_returns_open_palm() -> None:
    with build_client(hands=[_open_hand()]) as client:
        response = client.post("/classify", files=_files(), headers=HEADERS)
    assert response.status_code == 200
    body = response.json()
    assert body["success"] is True
    assert body["hands_detected"] == 1
    assert body["prediction"]["label"] == "open_palm"
    assert body["prediction"]["confidence"] == 1.0


def test_classify_returns_503_when_model_not_ready() -> None:
    with build_client(ready=False) as client:
        response = client.post("/classify", files=_files(), headers=HEADERS)
    assert response.status_code == 503
