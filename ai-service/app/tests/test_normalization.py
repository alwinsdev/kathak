"""Normalization tests: golden-fixture lock + translation/scale/rotation invariance."""

import math

import pytest

from app.domain.hand_landmarks.normalization import DegenerateHandError, normalize
from app.domain.hand_landmarks.topology import MIDDLE_MCP, WRIST
from app.tests.conftest import hand_from_points, load_landmark_fixture

TOL = 1e-6

_RAW = load_landmark_fixture("open_hand.json")
_RAW_POINTS = _RAW["landmarks"]


def _assert_matches_golden(landmarks, golden_points, tol: float = TOL) -> None:
    assert len(landmarks) == len(golden_points)
    for lm, gp in zip(landmarks, golden_points, strict=True):
        assert lm.x == pytest.approx(gp[0], abs=tol)
        assert lm.y == pytest.approx(gp[1], abs=tol)
        assert lm.z == pytest.approx(gp[2], abs=tol)


def _translate(points, dx, dy, dz):
    return [[x + dx, y + dy, z + dz] for x, y, z in points]


def _scale(points, factor):
    return [[x * factor, y * factor, z * factor] for x, y, z in points]


def _rotate_z(points, angle):
    cos_t, sin_t = math.cos(angle), math.sin(angle)
    return [[x * cos_t - y * sin_t, x * sin_t + y * cos_t, z] for x, y, z in points]


def test_normalize_matches_golden() -> None:
    golden = load_landmark_fixture("open_hand.normalized.json")["landmarks"]
    result = normalize(hand_from_points(_RAW_POINTS))
    _assert_matches_golden(result.landmarks, golden)


def test_wrist_moves_to_origin() -> None:
    result = normalize(hand_from_points(_RAW_POINTS))
    wrist = result.landmarks[WRIST]
    assert wrist.x == pytest.approx(0.0, abs=TOL)
    assert wrist.y == pytest.approx(0.0, abs=TOL)
    assert wrist.z == pytest.approx(0.0, abs=TOL)


def test_reference_axis_aligns_to_plus_y() -> None:
    # After rotation the wrist->middle-MCP axis points along +Y: x ~ 0, y > 0.
    result = normalize(hand_from_points(_RAW_POINTS))
    middle_mcp = result.landmarks[MIDDLE_MCP]
    assert middle_mcp.x == pytest.approx(0.0, abs=TOL)
    assert middle_mcp.y > 0.0


def test_scale_is_unit_normalized() -> None:
    # The wrist->middle-MCP distance becomes 1 after scale normalization.
    result = normalize(hand_from_points(_RAW_POINTS))
    m = result.landmarks[MIDDLE_MCP]
    assert math.sqrt(m.x**2 + m.y**2 + m.z**2) == pytest.approx(1.0, abs=TOL)


def test_translation_invariance() -> None:
    golden = load_landmark_fixture("open_hand.normalized.json")["landmarks"]
    moved = hand_from_points(_translate(_RAW_POINTS, 0.13, -0.27, 0.4))
    _assert_matches_golden(normalize(moved).landmarks, golden)


def test_scale_invariance() -> None:
    golden = load_landmark_fixture("open_hand.normalized.json")["landmarks"]
    bigger = hand_from_points(_scale(_RAW_POINTS, 2.5))
    _assert_matches_golden(normalize(bigger).landmarks, golden)


def test_in_plane_rotation_invariance() -> None:
    golden = load_landmark_fixture("open_hand.normalized.json")["landmarks"]
    rolled = hand_from_points(_rotate_z(_RAW_POINTS, 0.7))
    _assert_matches_golden(normalize(rolled).landmarks, golden)


def test_combined_transform_invariance() -> None:
    golden = load_landmark_fixture("open_hand.normalized.json")["landmarks"]
    transformed = _rotate_z(_scale(_translate(_RAW_POINTS, -0.2, 0.35, 0.1), 1.8), -1.1)
    _assert_matches_golden(normalize(hand_from_points(transformed)).landmarks, golden)


def test_handedness_is_preserved() -> None:
    result = normalize(hand_from_points(_RAW_POINTS, handedness="Left"))
    assert result.handedness == "Left"


def test_degenerate_hand_raises() -> None:
    # Wrist and middle-MCP coincide -> no stable scale reference.
    points = [[0.5, 0.5, 0.0] for _ in range(21)]
    with pytest.raises(DegenerateHandError):
        normalize(hand_from_points(points))
