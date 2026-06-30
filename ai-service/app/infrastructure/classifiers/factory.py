"""The single composition point for classifier providers.

``create_classifier(driver)`` maps a configured driver name to a concrete
``MudraClassifier``. This is the only place that imports concrete classifiers, so
adding a new provider (ml / tensorflow / onnx / pytorch / ...) means registering
it here once — no domain or application code changes.
"""

from collections.abc import Callable

from app.domain.mudra_classifier.classifier import MudraClassifier
from app.infrastructure.classifiers.rule_based.classifier import RuleBasedMudraClassifier
from app.infrastructure.classifiers.stub.classifier import StubMudraClassifier


class UnknownClassifierDriverError(ValueError):
    """The configured CLASSIFIER_DRIVER does not map to a known provider."""


# Driver name -> provider factory. Future drivers (ml, tensorflow, onnx, pytorch)
# register here and nowhere else.
_REGISTRY: dict[str, Callable[[], MudraClassifier]] = {
    "stub": StubMudraClassifier,
    "rule_based": RuleBasedMudraClassifier,
}


def available_drivers() -> tuple[str, ...]:
    return tuple(_REGISTRY)


def create_classifier(driver: str) -> MudraClassifier:
    """Build the classifier for ``driver``; raise if it is not registered."""
    try:
        provider_factory = _REGISTRY[driver]
    except KeyError:
        raise UnknownClassifierDriverError(
            f"Unknown classifier driver '{driver}'. Available: {', '.join(_REGISTRY)}."
        ) from None
    return provider_factory()
