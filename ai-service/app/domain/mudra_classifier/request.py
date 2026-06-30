"""Input model for the classification use case.

A request wraps a single detected hand to be classified. The application service
normalizes it, extracts features, and runs the configured classifier. Kept as a
small DTO so the use-case input can grow (e.g. options, thresholds) without
changing the classifier port.
"""

from dataclasses import dataclass

from app.domain.hand_landmarks.models import HandLandmarks


@dataclass(frozen=True)
class ClassificationRequest:
    """One hand to classify. ``hand`` may be ``None`` so callers can be validated
    rather than crashing on missing perception input."""

    hand: HandLandmarks | None
    correlation_id: str | None = None
