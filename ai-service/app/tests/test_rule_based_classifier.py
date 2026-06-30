"""RuleBasedMudraClassifier: open_palm / closed_fist / unknown + validation."""

import pytest

from app.domain.mudra_classifier.classifier import MudraClassifier
from app.domain.mudra_classifier.exceptions import InvalidFeaturesError
from app.domain.mudra_classifier.metadata import CLASSIFIER_TYPE, MODEL_VERSION
from app.domain.mudra_classifier.models import HandFeatures
from app.infrastructure.classifiers.rule_based.classifier import (
    CLOSED_FIST_MIN_CURL,
    OPEN_PALM_MAX_CURL,
    RuleBasedMudraClassifier,
)


def _features(curls: dict) -> HandFeatures:
    return HandFeatures(
        finger_curls=dict(curls), finger_angles={}, adjacent_spreads={}, key_distances={}
    )


def _uniform(value: float) -> dict:
    return {"thumb": value, "index": value, "middle": value, "ring": value, "pinky": value}


def test_implements_the_port() -> None:
    assert isinstance(RuleBasedMudraClassifier(), MudraClassifier)


def test_open_palm_is_recognized() -> None:
    result = RuleBasedMudraClassifier().classify(_features(_uniform(5.0)))
    assert result.label == "open_palm"
    assert result.confidence == 1.0
    assert result.metadata[CLASSIFIER_TYPE] == "rule_based"
    assert result.metadata[MODEL_VERSION]


def test_closed_fist_is_recognized() -> None:
    result = RuleBasedMudraClassifier().classify(_features(_uniform(160.0)))
    assert result.label == "closed_fist"
    assert result.confidence == 1.0


def test_midrange_curl_is_unknown() -> None:
    result = RuleBasedMudraClassifier().classify(_features(_uniform(80.0)))
    assert result.label == "unknown"
    assert result.confidence == 0.0


def test_mixed_fingers_are_unknown() -> None:
    # Some extended, some curled -> matches neither rule.
    curls = {"thumb": 5.0, "index": 5.0, "middle": 160.0, "ring": 5.0, "pinky": 5.0}
    assert RuleBasedMudraClassifier().classify(_features(curls)).label == "unknown"


def test_open_palm_boundary_is_inclusive() -> None:
    result = RuleBasedMudraClassifier().classify(_features(_uniform(OPEN_PALM_MAX_CURL)))
    assert result.label == "open_palm"


def test_closed_fist_boundary_is_inclusive() -> None:
    result = RuleBasedMudraClassifier().classify(_features(_uniform(CLOSED_FIST_MIN_CURL)))
    assert result.label == "closed_fist"


def test_just_past_boundaries_are_unknown() -> None:
    classifier = RuleBasedMudraClassifier()
    assert classifier.classify(_features(_uniform(OPEN_PALM_MAX_CURL + 0.1))).label == "unknown"
    assert classifier.classify(_features(_uniform(CLOSED_FIST_MIN_CURL - 0.1))).label == "unknown"


def test_empty_feature_vector_raises() -> None:
    with pytest.raises(InvalidFeaturesError):
        RuleBasedMudraClassifier().classify(_features({}))


def test_incomplete_feature_vector_raises() -> None:
    # Missing the pinky curl.
    curls = {"thumb": 5.0, "index": 5.0, "middle": 5.0, "ring": 5.0}
    with pytest.raises(InvalidFeaturesError):
        RuleBasedMudraClassifier().classify(_features(curls))
