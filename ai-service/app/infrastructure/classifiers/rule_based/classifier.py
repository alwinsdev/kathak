"""Rule-based mudra classifier — the first real ``MudraClassifier`` provider.

Its sole purpose is to validate the inference pipeline end-to-end. It recognizes
only two coarse hand shapes from finger curl:

* **open_palm** — all four non-thumb fingers extended (low curl);
* **closed_fist** — all four non-thumb fingers curled (high curl);

anything else is **unknown**. This is a demonstration of the architecture, **not**
real mudra recognition — the thresholds are illustrative constants. Real mudra
recognition (and ML providers) arrive in later phases, all implementing this same
``MudraClassifier`` contract.
"""

from app.domain.mudra_classifier.classifier import MudraClassifier
from app.domain.mudra_classifier.exceptions import InvalidFeaturesError
from app.domain.mudra_classifier.metadata import CLASSIFIER_TYPE, MODEL_VERSION
from app.domain.mudra_classifier.models import ClassificationResult, HandFeatures

CLASSIFIER_TYPE_NAME = "rule_based"
MODEL_VERSION_VALUE = "rule_based-0.1.0"

# Illustrative thresholds (degrees of curl), inclusive at the boundary.
OPEN_PALM_MAX_CURL = 50.0
CLOSED_FIST_MIN_CURL = 120.0

# Thumb is excluded: it is the least reliable for these two coarse shapes.
_REQUIRED_FINGERS = ("index", "middle", "ring", "pinky")

LABEL_OPEN_PALM = "open_palm"
LABEL_CLOSED_FIST = "closed_fist"
LABEL_UNKNOWN = "unknown"


class RuleBasedMudraClassifier(MudraClassifier):
    """Classify open_palm / closed_fist from finger-curl rules; else unknown."""

    def classify(self, features: HandFeatures) -> ClassificationResult:
        self._validate(features)
        curls = [features.finger_curls[finger] for finger in _REQUIRED_FINGERS]

        if all(curl <= OPEN_PALM_MAX_CURL for curl in curls):
            label, confidence, reason = (
                LABEL_OPEN_PALM,
                1.0,
                "All non-thumb fingers extended (low curl).",
            )
        elif all(curl >= CLOSED_FIST_MIN_CURL for curl in curls):
            label, confidence, reason = (
                LABEL_CLOSED_FIST,
                1.0,
                "All non-thumb fingers curled (high curl).",
            )
        else:
            label, confidence, reason = (
                LABEL_UNKNOWN,
                0.0,
                "Finger curls match neither the open-palm nor the closed-fist rule.",
            )

        return ClassificationResult(
            label=label,
            confidence=confidence,
            reason=reason,
            metadata={
                CLASSIFIER_TYPE: CLASSIFIER_TYPE_NAME,
                MODEL_VERSION: MODEL_VERSION_VALUE,
            },
        )

    @staticmethod
    def _validate(features: HandFeatures) -> None:
        curls = features.finger_curls
        if not curls:
            raise InvalidFeaturesError("Empty feature vector: no finger curls present.")
        missing = [finger for finger in _REQUIRED_FINGERS if finger not in curls]
        if missing:
            raise InvalidFeaturesError(
                f"Invalid feature vector: missing finger curls for {missing}."
            )
