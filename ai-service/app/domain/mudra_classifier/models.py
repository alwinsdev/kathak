"""Domain models for mudra classification (ML-agnostic).

``HandFeatures`` is a geometric, explainable description of a hand pose — no ML
or library terminology — so any classifier (rule-based, ML, hybrid) can consume
it. ``ClassificationResult`` is deliberately extensible via ``metadata`` so later
phases can add detail (per-feature scores, alternative candidates, model
version) without breaking the contract.
"""

from dataclasses import dataclass, field


@dataclass(frozen=True)
class HandFeatures:
    """Geometric description of one hand's pose, derived from normalized landmarks.

    Because the inputs are normalized, every value is invariant to hand position,
    size, and in-plane rotation. Dictionaries are keyed by finger / finger-pair
    name for readability and additive extension.
    """

    finger_curls: dict[str, float]  # degrees; ~0 = straight, larger = more curled
    finger_angles: dict[str, float]  # degrees of finger direction vs the palm axis
    adjacent_spreads: dict[str, float]  # degrees between adjacent finger directions
    key_distances: dict[str, float]  # normalized (scale-free) landmark distances


@dataclass(frozen=True)
class ClassificationResult:
    """Outcome of a classification attempt.

    ``label`` and ``confidence`` are the core result; ``reason`` is a
    human-readable explanation; ``metadata`` is an open bag for future fields so
    the contract can grow without breaking existing consumers.
    """

    label: str
    confidence: float
    reason: str = ""
    metadata: dict[str, object] = field(default_factory=dict)
