"""Stub mudra classifier (Phase 3 foundation).

A placeholder implementation of the ``MudraClassifier`` port that performs **no**
recognition — it always returns an ``unrecognized`` result. It exists so the
contract and wiring can be exercised end-to-end before any rule engine or ML
model is built (those arrive in a later phase).
"""

from app.domain.mudra_classifier.classifier import MudraClassifier
from app.domain.mudra_classifier.models import ClassificationResult, HandFeatures

UNRECOGNIZED_LABEL = "unrecognized"


class StubMudraClassifier(MudraClassifier):
    """Always returns an 'unrecognized' result with zero confidence."""

    def classify(self, features: HandFeatures) -> ClassificationResult:  # noqa: ARG002
        return ClassificationResult(
            label=UNRECOGNIZED_LABEL,
            confidence=0.0,
            reason="Mudra classification is not implemented yet (Phase 3 foundation).",
            metadata={"stub": True},
        )
