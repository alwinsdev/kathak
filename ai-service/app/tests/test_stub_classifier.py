"""Stub classifier tests: it honours the port and performs no recognition."""

from app.domain.hand_landmarks.normalization import normalize
from app.domain.mudra_classifier.classifier import MudraClassifier
from app.domain.mudra_classifier.feature_extraction import FeatureExtractionService
from app.infrastructure.classifiers.stub.classifier import StubMudraClassifier
from app.tests.conftest import hand_from_points, load_landmark_fixture

_RAW_POINTS = load_landmark_fixture("open_hand.json")["landmarks"]


def _real_features():
    return FeatureExtractionService().extract(normalize(hand_from_points(_RAW_POINTS)))


def test_stub_implements_the_port() -> None:
    assert isinstance(StubMudraClassifier(), MudraClassifier)


def test_stub_returns_unrecognized_zero_confidence() -> None:
    result = StubMudraClassifier().classify(_real_features())
    assert result.label == "unrecognized"
    assert result.confidence == 0.0


def test_stub_result_is_extensible_and_explained() -> None:
    result = StubMudraClassifier().classify(_real_features())
    assert result.reason  # non-empty human-readable explanation
    assert isinstance(result.metadata, dict)
    assert result.metadata.get("stub") is True
