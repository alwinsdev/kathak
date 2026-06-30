"""Pure-Python 3D vector helpers for domain geometry.

Vectors are plain ``(x, y, z)`` tuples so the domain stays dependency-free (no
numpy) and the math is trivial to unit-test. Shared by landmark normalization
(perception) and feature extraction (classification).
"""

import math

Vec3 = tuple[float, float, float]


def sub(a: Vec3, b: Vec3) -> Vec3:
    return (a[0] - b[0], a[1] - b[1], a[2] - b[2])


def scale(a: Vec3, factor: float) -> Vec3:
    return (a[0] * factor, a[1] * factor, a[2] * factor)


def dot(a: Vec3, b: Vec3) -> float:
    return a[0] * b[0] + a[1] * b[1] + a[2] * b[2]


def norm(a: Vec3) -> float:
    return math.sqrt(dot(a, a))


def distance(a: Vec3, b: Vec3) -> float:
    return norm(sub(a, b))


def angle_between(a: Vec3, b: Vec3) -> float:
    """Angle between two vectors, in degrees. Returns 0.0 if either is null."""
    na, nb = norm(a), norm(b)
    if na == 0.0 or nb == 0.0:
        return 0.0
    cosine = dot(a, b) / (na * nb)
    cosine = max(-1.0, min(1.0, cosine))  # guard float drift outside [-1, 1]
    return math.degrees(math.acos(cosine))


def rotate_z(p: Vec3, cos_t: float, sin_t: float) -> Vec3:
    """Rotate a point about the z-axis by the angle whose cos/sin are given."""
    x, y, z = p
    return (x * cos_t - y * sin_t, x * sin_t + y * cos_t, z)


def centroid(points: list[Vec3]) -> Vec3:
    """Arithmetic mean of a non-empty list of points."""
    count = len(points)
    sx = sum(p[0] for p in points)
    sy = sum(p[1] for p in points)
    sz = sum(p[2] for p in points)
    return (sx / count, sy / count, sz / count)
