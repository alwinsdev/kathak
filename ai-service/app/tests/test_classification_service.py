"""ClassificationService: end-to-end orchestration + reserved metadata + guards."""

from datetime import datetime

import pytest

from app.application.mudra_classifier.service import ClassificationService
from app.domain.mudra_classifier.exceptions import InvalidFeaturesError
from app.domain.mudra_classifier.metadata import (
    CLASSIFIER_TYPE,
    CONFIDENCE,
    MODEL_VERSION,
    PREDICTION_TIMESTAMP,
    RESERVED_KEYS,
)
from app.domain.mudra_classifier.request import ClassificationRequest
from app.infrastructure.classifiers.rule_based.classifier import RuleBasedMudraClassifier
from app.infrastructure.classifiers.stub.classifier import StubMudraClassifier
from app.tests.conftest import hand_from_points, load_landmark_fixture


def _request_from_fixture(name: str) -> ClassificationRequest:
    points = load_landmark_fixture(name)["landmarks"]
    return ClassificationRequest(hand=hand_from_points(points))


def _rule_based_service() -> ClassificationService:
    return ClassificationService(RuleBasedMudraClassifier())


def test_open_hand_fixture_classifies_as_open_palm() -> None:
    result = _rule_based_service().classify(_request_from_fixture("open_hand.json"))
    assert result.label == "open_palm"


def test_closed_fist_fixture_classifies_as_closed_fist() -> None:
    result = _rule_based_service().classify(_request_from_fixture("closed_fist.json"))
    assert result.label == "closed_fist"


def test_reserved_metadata_keys_are_present() -> None:
    result = _rule_based_service().classify(_request_from_fixture("open_hand.json"))
    for key in RESERVED_KEYS:
        assert key in result.metadata
    assert result.metadata[CLASSIFIER_TYPE] == "rule_based"
    assert result.metadata[MODEL_VERSION]
    assert result.metadata[CONFIDENCE] == result.confidence
    # prediction_timestamp is a parseable ISO-8601 instant.
    assert datetime.fromisoformat(result.metadata[PREDICTION_TIMESTAMP])


def test_service_is_provider_agnostic_with_stub() -> None:
    # Same service, different provider, no code change.
    result = ClassificationService(StubMudraClassifier()).classify(
        _request_from_fixture("open_hand.json")
    )
    assert result.label == "unrecognized"
    # Reserved keys still guaranteed; the stub supplies no type/version.
    assert result.metadata[CLASSIFIER_TYPE] is None
    assert result.metadata[MODEL_VERSION] is None
    assert result.metadata[PREDICTION_TIMESTAMP]


def test_null_landmarks_raise() -> None:
    with pytest.raises(InvalidFeaturesError):
        _rule_based_service().classify(ClassificationRequest(hand=None))


def test_empty_landmarks_raise() -> None:
    empty_hand = hand_from_points([])
    with pytest.raises(InvalidFeaturesError):
        _rule_based_service().classify(ClassificationRequest(hand=empty_hand))
