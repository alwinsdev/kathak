"""The mudra-classifier port (provider-agnostic).

The domain depends only on this abstraction. Concrete classifiers — rule-based,
ML, or hybrid — live in the infrastructure layer and are introduced in a later
phase, mirroring how the MediaPipe landmark provider sits behind its port.
"""

from abc import ABC, abstractmethod

from app.domain.mudra_classifier.models import ClassificationResult, HandFeatures


class MudraClassifier(ABC):
    """Classify a hand's geometric features into a mudra label."""

    @abstractmethod
    def classify(self, features: HandFeatures) -> ClassificationResult:
        """Return the classification for the given features."""
        raise NotImplementedError
