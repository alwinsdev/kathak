"""Landmark normalization — part of perception, not classification.

Maps raw ``HandLandmarks`` into a ``NormalizedHandLandmarks`` frame that is
invariant to:

* **translation** — the wrist is moved to the origin;
* **scale** — coordinates are divided by the wrist->middle-MCP distance, so
  hand size and camera distance drop out;
* **in-plane (2D) rotation** — the hand is rotated about the z (camera) axis so
  the wrist->middle-MCP axis points along +Y.

Downstream feature extraction therefore sees hand *shape* independent of where
the hand sat in frame, how large it appeared, or how it was rolled about the
camera axis. Full 3D canonicalization (out-of-plane tilt) and handedness
mirroring are intentionally **deferred to a later phase**.
"""

import math

from app.domain import geometry as g
from app.domain.hand_landmarks.models import (
    HandLandmarks,
    Landmark,
    NormalizedHandLandmarks,
)
from app.domain.hand_landmarks.topology import MIDDLE_MCP, WRIST


class DegenerateHandError(ValueError):
    """The hand geometry is degenerate (e.g. wrist and middle-MCP coincide), so
    a stable normalized frame cannot be derived."""


def normalize(hand: HandLandmarks) -> NormalizedHandLandmarks:
    """Return the hand's landmarks in the canonical, pose-invariant frame."""
    points: list[g.Vec3] = [(lm.x, lm.y, lm.z) for lm in hand.landmarks]

    # 1. Translation: move the wrist to the origin.
    wrist = points[WRIST]
    translated = [g.sub(p, wrist) for p in points]

    # 2. Scale: divide by the wrist->middle-MCP distance.
    reference = g.norm(translated[MIDDLE_MCP])
    if reference == 0.0:
        raise DegenerateHandError("Wrist and middle-MCP coincide; cannot scale-normalize.")
    scaled = [g.scale(p, 1.0 / reference) for p in translated]

    # 3. In-plane rotation: rotate about z so the wrist->middle-MCP axis is +Y.
    axis = scaled[MIDDLE_MCP]
    current_angle = math.atan2(axis[1], axis[0])
    rotation = (math.pi / 2.0) - current_angle
    cos_t, sin_t = math.cos(rotation), math.sin(rotation)
    rotated = [g.rotate_z(p, cos_t, sin_t) for p in scaled]

    return NormalizedHandLandmarks(
        handedness=hand.handedness,
        landmarks=[Landmark(x=p[0], y=p[1], z=p[2]) for p in rotated],
    )
