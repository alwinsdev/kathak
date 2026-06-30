"""Pure domain models for the hand-landmark capability.

These represent business concepts only — no MediaPipe (or any library) types or
terminology. Provider implementations map their native results into these
models inside the infrastructure layer before anything reaches the domain.
"""

from dataclasses import dataclass
from datetime import datetime

# A hand model emits a fixed topology of 21 landmarks. Anything else is
# malformed perception data and must not reach the classifier.
EXPECTED_LANDMARK_COUNT = 21


@dataclass(frozen=True)
class Landmark:
    """A single normalized hand landmark (coordinates in [0, 1], z relative)."""

    x: float
    y: float
    z: float


@dataclass(frozen=True)
class BoundingBox:
    """Approximate, center-based bounding box in image pixels.

    NOTE: this is derived from the landmark extents, NOT a native
    object-detection box. Consumers must not assume pixel-perfect detection
    accuracy.
    """

    cx: float
    cy: float
    width: float
    height: float


@dataclass(frozen=True)
class HandLandmarks:
    """One detected hand: its handedness, score, box and 21 landmarks."""

    handedness: str
    score: float
    bbox: BoundingBox
    landmarks: list[Landmark]


@dataclass(frozen=True)
class NormalizedHandLandmarks:
    """The 21 landmarks of one hand in a canonical, pose-invariant frame.

    Produced by ``normalize()``: the wrist sits at the origin
    (translation-invariant), coordinates are scaled by the wrist->middle-MCP
    distance (scale-invariant), and the hand is rotated in the image plane so
    that axis points along +Y (2D rotation-invariant). Full 3D canonicalization
    and handedness mirroring are deferred to a later phase.
    """

    handedness: str
    landmarks: list[Landmark]


@dataclass(frozen=True)
class HandDetections:
    """Perception output of a provider for a single image."""

    hands: list[HandLandmarks]
    image_width: int
    image_height: int


@dataclass(frozen=True)
class DetectionResult:
    """Stable detection contract returned by the service.

    Frozen as of Phase 2. Later phases (classification, explainable AI, Laravel
    integration) may extend it additively but must not introduce breaking
    changes.
    """

    hands: list[HandLandmarks]
    image_width: int
    image_height: int
    processing_time_ms: int
    detected_at: datetime
    correlation_id: str | None
