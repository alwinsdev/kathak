"""Health endpoint tests."""

from fastapi.testclient import TestClient


def test_health_returns_healthy_when_provider_ready(client: TestClient) -> None:
    response = client.get("/health")

    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "healthy"
    assert body["service"] == "mediapipe-ai"
    assert body["model_loaded"] is True
    for key in ("version", "mediapipe_version", "python_version", "environment", "uptime"):
        assert key in body, f"missing health field: {key}"


def test_health_echoes_correlation_id(client: TestClient) -> None:
    response = client.get("/health", headers={"X-Correlation-ID": "abc-123"})

    assert response.headers.get("X-Correlation-ID") == "abc-123"


def test_health_generates_correlation_id_when_absent(client: TestClient) -> None:
    response = client.get("/health")

    assert response.headers.get("X-Correlation-ID")  # non-empty


def test_health_returns_503_when_provider_not_ready(degraded_client: TestClient) -> None:
    response = degraded_client.get("/health")

    assert response.status_code == 503
    body = response.json()
    assert body["status"] == "unhealthy"
    assert body["model_loaded"] is False
