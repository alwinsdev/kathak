"""Rule-based mudra classifier — the first real ``MudraClassifier`` provider.

Its sole purpose is to validate the inference pipeline end-to-end. It recognizes
two coarse hand shapes from how much the fingers curl:

* **open_palm** — fingers extended (low mean curl);
* **closed_fist** — fingers curled (high mean curl);

anything in between is **unknown**. It uses the **mean** curl of the four
non-thumb fingers (not every finger past a hard threshold), which is forgiving of
real hands where one finger sits slightly differently. This is a demonstration of
the architecture, **not** full mudra recognition — real recognition (and ML
providers) arrive in later phases, all implementing this ``MudraClassifier``
contract.
"""

from app.domain.mudra_classifier.classifier import MudraClassifier
from app.domain.mudra_classifier.exceptions import InvalidFeaturesError
from app.domain.mudra_classifier.metadata import CLASSIFIER_TYPE, MODEL_VERSION
from app.domain.mudra_classifier.models import ClassificationResult, HandFeatures

CLASSIFIER_TYPE_NAME = "rule_based"
MODEL_VERSION_VALUE = "rule_based-0.3.0"

# Mean finger-curl thresholds (degrees). Open hands read very low and fists very
# high, so a generous fist threshold catches real (loose) fists while keeping the
# two gestures clearly separated; the gap between them stays "unknown".
OPEN_PALM_MAX_MEAN_CURL = 55.0
CLOSED_FIST_MIN_MEAN_CURL = 78.0

# Thumb is excluded: it is the least reliable for these two coarse shapes.
_REQUIRED_FINGERS = ("index", "middle", "ring", "pinky")

LABEL_OPEN_PALM = "open_palm"
LABEL_CLOSED_FIST = "closed_fist"
LABEL_UNKNOWN = "unknown"


class RuleBasedMudraClassifier(MudraClassifier):
    """Classify open_palm / closed_fist from the mean finger curl; else unknown."""

    def classify(self, features: HandFeatures) -> ClassificationResult:
        self._validate(features)
        curls = {finger: features.finger_curls[finger] for finger in _REQUIRED_FINGERS}
        mean_curl = sum(curls.values()) / len(curls)

        if mean_curl <= OPEN_PALM_MAX_MEAN_CURL:
            label, confidence, reason = (
                LABEL_OPEN_PALM,
                1.0,
                "Fingers extended (low mean curl).",
            )
        elif mean_curl >= CLOSED_FIST_MIN_MEAN_CURL:
            label, confidence, reason = (
                LABEL_CLOSED_FIST,
                1.0,
                "Fingers curled (high mean curl).",
            )
        else:
            label, confidence, reason = (
                LABEL_UNKNOWN,
                0.0,
                "Mean finger curl is between the open-palm and closed-fist ranges.",
            )

        return ClassificationResult(
            label=label,
            confidence=confidence,
            reason=reason,
            metadata={
                CLASSIFIER_TYPE: CLASSIFIER_TYPE_NAME,
                MODEL_VERSION: MODEL_VERSION_VALUE,
                # Derived features (not coordinates) — handy for tuning live demos.
                "mean_curl": round(mean_curl, 1),
                "finger_curls": {finger: round(curl, 1) for finger, curl in curls.items()},
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
