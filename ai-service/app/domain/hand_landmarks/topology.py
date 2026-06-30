"""Hand-landmark topology: 21-point index constants and finger groupings.

This is the standard hand model: one wrist point plus four joints per finger.
It lives in the perception domain because the index layout is a property of how
hands are landmarked, not of how mudras are classified.
"""

WRIST = 0

THUMB_CMC, THUMB_MCP, THUMB_IP, THUMB_TIP = 1, 2, 3, 4
INDEX_MCP, INDEX_PIP, INDEX_DIP, INDEX_TIP = 5, 6, 7, 8
MIDDLE_MCP, MIDDLE_PIP, MIDDLE_DIP, MIDDLE_TIP = 9, 10, 11, 12
RING_MCP, RING_PIP, RING_DIP, RING_TIP = 13, 14, 15, 16
PINKY_MCP, PINKY_PIP, PINKY_DIP, PINKY_TIP = 17, 18, 19, 20

LANDMARK_COUNT = 21

FINGERS = ("thumb", "index", "middle", "ring", "pinky")

# (base_mcp, middle_joint, tip) per finger — the three points used to measure the
# bend ("curl") at the middle joint. The thumb uses its IP joint as the middle.
FINGER_JOINTS: dict[str, tuple[int, int, int]] = {
    "thumb": (THUMB_MCP, THUMB_IP, THUMB_TIP),
    "index": (INDEX_MCP, INDEX_PIP, INDEX_TIP),
    "middle": (MIDDLE_MCP, MIDDLE_PIP, MIDDLE_TIP),
    "ring": (RING_MCP, RING_PIP, RING_TIP),
    "pinky": (PINKY_MCP, PINKY_PIP, PINKY_TIP),
}

FINGER_MCP: dict[str, int] = {
    "thumb": THUMB_MCP,
    "index": INDEX_MCP,
    "middle": MIDDLE_MCP,
    "ring": RING_MCP,
    "pinky": PINKY_MCP,
}

FINGER_TIP: dict[str, int] = {
    "thumb": THUMB_TIP,
    "index": INDEX_TIP,
    "middle": MIDDLE_TIP,
    "ring": RING_TIP,
    "pinky": PINKY_TIP,
}

# The four finger MCP knuckles — their centroid approximates the palm centre.
MCP_LANDMARKS = (INDEX_MCP, MIDDLE_MCP, RING_MCP, PINKY_MCP)

ADJACENT_FINGER_PAIRS = (
    ("thumb", "index"),
    ("index", "middle"),
    ("middle", "ring"),
    ("ring", "pinky"),
)
