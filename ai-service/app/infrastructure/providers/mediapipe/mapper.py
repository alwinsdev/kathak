"""Map MediaPipe HandLandmarker output into pure domain models.

This is the only place that understands MediaPipe's result shape; the domain
never sees a MediaPipe type.
"""

from app.domain.hand_landmarks.models import BoundingBox, HandLandmarks, Landmark


def map_hands(mp_result, image_width: int, image_height: int) -> list[HandLandmarks]:
    """Convert a MediaPipe HandLandmarkerResult into domain HandLandmarks."""
    hands: list[HandLandmarks] = []

    hand_landmark_sets = getattr(mp_result, "hand_landmarks", None) or []
    handedness_sets = getattr(mp_result, "handedness", None) or []

    for index, landmark_set in enumerate(hand_landmark_sets):
        landmarks = [Landmark(x=lm.x, y=lm.y, z=lm.z) for lm in landmark_set]

        handedness = "Unknown"
        score = 0.0
        if index < len(handedness_sets) and handedness_sets[index]:
            category = handedness_sets[index][0]
            handedness = category.category_name
            score = float(category.score)

        hands.append(
            HandLandmarks(
                handedness=handedness,
                score=score,
                bbox=_bbox_from_landmarks(landmarks, image_width, image_height),
                landmarks=landmarks,
            )
        )

    return hands


def _bbox_from_landmarks(
    landmarks: list[Landmark], image_width: int, image_height: int
) -> BoundingBox:
    """Approximate, center-based pixel box from the landmark extents."""
    xs = [lm.x for lm in landmarks]
    ys = [lm.y for lm in landmarks]
    min_x, max_x = min(xs), max(xs)
    min_y, max_y = min(ys), max(ys)

    return BoundingBox(
        cx=(min_x + max_x) / 2 * image_width,
        cy=(min_y + max_y) / 2 * image_height,
        width=(max_x - min_x) * image_width,
        height=(max_y - min_y) * image_height,
    )
