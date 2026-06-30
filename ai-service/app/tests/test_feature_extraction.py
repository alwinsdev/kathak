"""Feature extraction tests: structure + geometric correctness on known poses."""

from app.domain.hand_landmarks.normalization import normalize
from app.domain.hand_landmarks.topology import (
    ADJACENT_FINGER_PAIRS,
    FINGER_JOINTS,
    FINGERS,
)
from app.domain.mudra_classifier.feature_extraction import FeatureExtractionService
from app.tests.conftest import hand_from_points, load_landmark_fixture

_RAW_POINTS = load_landmark_fixture("open_hand.json")["landmarks"]


def _features_from(points):
    return FeatureExtractionService().extract(normalize(hand_from_points(points)))


def test_features_have_expected_structure() -> None:
    features = _features_from(_RAW_POINTS)
    assert set(features.finger_curls) == set(FINGERS)
    assert set(features.finger_angles) == set(FINGERS)
    assert set(features.adjacent_spreads) == {f"{a}_{b}" for a, b in ADJACENT_FINGER_PAIRS}
    # 4 thumb-opposition + 5 tip-wrist + 5 tip-palm distances.
    assert len(features.key_distances) == 14
    assert all(v >= 0.0 for v in features.key_distances.values())


def test_extended_fingers_have_low_curl() -> None:
    # The open-hand fixture has straight fingers -> small curl values.
    features = _features_from(_RAW_POINTS)
    for finger in ("index", "middle", "ring", "pinky"):
        assert features.finger_curls[finger] < 45.0


def test_colinear_finger_has_near_zero_curl() -> None:
    # Force the index joints (MCP, PIP, TIP) to be perfectly colinear.
    points = [list(p) for p in _RAW_POINTS]
    mcp_i, pip_i, tip_i = FINGER_JOINTS["index"]
    points[mcp_i] = [0.46, 0.66, 0.0]
    points[pip_i] = [0.46, 0.56, 0.0]
    points[tip_i] = [0.46, 0.46, 0.0]
    features = _features_from(points)
    assert features.finger_curls["index"] < 1.0


def test_curl_increases_when_finger_bends() -> None:
    straight = _features_from(_RAW_POINTS).finger_curls["index"]

    # Bend the index by pulling its tip back toward the palm/wrist.
    bent_points = [list(p) for p in _RAW_POINTS]
    _, _, tip_i = FINGER_JOINTS["index"]
    bent_points[tip_i] = [0.470, 0.620, 0.05]  # tip near the MCP -> strong bend
    bent = _features_from(bent_points).finger_curls["index"]

    assert bent > straight


def test_spread_between_adjacent_fingers_is_nonnegative() -> None:
    features = _features_from(_RAW_POINTS)
    for value in features.adjacent_spreads.values():
        assert value >= 0.0
