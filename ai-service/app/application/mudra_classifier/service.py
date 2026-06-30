"""Application service orchestrating the classification use case.

Runs the full inference flow for one hand:

    ClassificationRequest -> normalize -> extract features -> classifier -> result

It depends only on the ``MudraClassifier`` port, so any provider (stub,
rule-based, future ML) plugs in without changing this service. After classifying,
it guarantees the reserved metadata keys are present on the result.
"""

from dataclasses import replace
from datetime import UTC, datetime

from app.domain.hand_landmarks.normalization import normalize
from app.domain.mudra_classifier.classifier import MudraClassifier
from app.domain.mudra_classifier.exceptions import InvalidFeaturesError
from app.domain.mudra_classifier.feature_extraction import FeatureExtractionService
from app.domain.mudra_classifier.metadata import (
    CLASSIFIER_TYPE,
    CONFIDENCE,
    MODEL_VERSION,
    PREDICTION_TIMESTAMP,
)
from app.domain.mudra_classifier.models import ClassificationResult
from app.domain.mudra_classifier.request import ClassificationRequest


class ClassificationService:
    """Coordinate normalization, feature extraction, and classification."""

    def __init__(
        self,
        classifier: MudraClassifier,
        feature_extractor: FeatureExtractionService | None = None,
    ) -> None:
        self._classifier = classifier
        self._features = feature_extractor or FeatureExtractionService()

    def classify(self, request: ClassificationRequest) -> ClassificationResult:
        hand = request.hand
        if hand is None or not hand.landmarks:
            raise InvalidFeaturesError("Null or empty landmarks: nothing to classify.")

        normalized = normalize(hand)
        features = self._features.extract(normalized)
        result = self._classifier.classify(features)
        return self._finalize(result)

    @staticmethod
    def _finalize(result: ClassificationResult) -> ClassificationResult:
        """Guarantee the reserved metadata keys are present on every result."""
        metadata = dict(result.metadata)
        metadata.setdefault(MODEL_VERSION, None)
        metadata.setdefault(CLASSIFIER_TYPE, None)
        metadata.setdefault(CONFIDENCE, result.confidence)
        metadata[PREDICTION_TIMESTAMP] = datetime.now(UTC).isoformat()
        return replace(result, metadata=metadata)
