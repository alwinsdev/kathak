"""Factory: the single composition point mapping driver -> provider."""

import pytest

from app.domain.mudra_classifier.classifier import MudraClassifier
from app.infrastructure.classifiers.factory import (
    UnknownClassifierDriverError,
    available_drivers,
    create_classifier,
)
from app.infrastructure.classifiers.rule_based.classifier import RuleBasedMudraClassifier
from app.infrastructure.classifiers.stub.classifier import StubMudraClassifier


def test_creates_rule_based_provider() -> None:
    provider = create_classifier("rule_based")
    assert isinstance(provider, RuleBasedMudraClassifier)
    assert isinstance(provider, MudraClassifier)


def test_creates_stub_provider() -> None:
    provider = create_classifier("stub")
    assert isinstance(provider, StubMudraClassifier)
    assert isinstance(provider, MudraClassifier)


def test_unknown_driver_raises() -> None:
    with pytest.raises(UnknownClassifierDriverError):
        create_classifier("does_not_exist")


def test_available_drivers_lists_registered() -> None:
    drivers = available_drivers()
    assert "stub" in drivers
    assert "rule_based" in drivers
