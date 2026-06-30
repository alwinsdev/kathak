"""Extract explainable geometric features from normalized hand landmarks.

Turns a ``NormalizedHandLandmarks`` into ``HandFeatures`` — per-finger curl and
direction, inter-finger spread, and key distances. These are the human-meaningful
quantities ("the index is straight", "the thumb is far from the index") that a
later classification phase will threshold or learn from. No classification,
thresholds, or ML happen here.
"""

from app.domain import geometry as g
from app.domain.hand_landmarks.models import NormalizedHandLandmarks
from app.domain.hand_landmarks.topology import (
    ADJACENT_FINGER_PAIRS,
    FINGER_JOINTS,
    FINGER_TIP,
    FINGERS,
    MCP_LANDMARKS,
    MIDDLE_MCP,
    WRIST,
)
from app.domain.mudra_classifier.models import HandFeatures

# Fingertips other than the thumb — used for thumb-opposition distances.
_NON_THUMB_FINGERS = ("index", "middle", "ring", "pinky")


class FeatureExtractionService:
    """Stateless service computing ``HandFeatures`` from normalized landmarks."""

    def extract(self, normalized: NormalizedHandLandmarks) -> HandFeatures:
        points: list[g.Vec3] = [(lm.x, lm.y, lm.z) for lm in normalized.landmarks]

        palm_axis = g.sub(points[MIDDLE_MCP], points[WRIST])
        palm_center = g.centroid([points[i] for i in MCP_LANDMARKS])

        finger_curls: dict[str, float] = {}
        finger_angles: dict[str, float] = {}
        finger_directions: dict[str, g.Vec3] = {}

        for finger in FINGERS:
            mcp_i, mid_i, tip_i = FINGER_JOINTS[finger]
            mcp, mid, tip = points[mcp_i], points[mid_i], points[tip_i]

            # Curl: how much the finger bends at its middle joint. A straight
            # finger gives ~180 deg between the two segments, hence curl ~0.
            bend = g.angle_between(g.sub(mcp, mid), g.sub(tip, mid))
            finger_curls[finger] = 180.0 - bend

            direction = g.sub(tip, mcp)
            finger_directions[finger] = direction
            finger_angles[finger] = g.angle_between(direction, palm_axis)

        adjacent_spreads = {
            f"{a}_{b}": g.angle_between(finger_directions[a], finger_directions[b])
            for a, b in ADJACENT_FINGER_PAIRS
        }

        key_distances: dict[str, float] = {}
        thumb_tip = points[FINGER_TIP["thumb"]]
        for finger in _NON_THUMB_FINGERS:
            key_distances[f"thumb_{finger}_tip"] = g.distance(thumb_tip, points[FINGER_TIP[finger]])
        for finger in FINGERS:
            tip = points[FINGER_TIP[finger]]
            key_distances[f"{finger}_tip_wrist"] = g.distance(tip, points[WRIST])
            key_distances[f"{finger}_tip_palm"] = g.distance(tip, palm_center)

        return HandFeatures(
            finger_curls=finger_curls,
            finger_angles=finger_angles,
            adjacent_spreads=adjacent_spreads,
            key_distances=key_distances,
        )
