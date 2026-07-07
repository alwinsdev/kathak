<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mudra practice guides
|--------------------------------------------------------------------------
|
| Patient-facing teaching content for the practice screen, keyed by the
| mudra's ai_class_label (the model's class token). Kept in config (not the
| DB) so it can be edited without a schema change. A 'default' entry is used
| for any mudra without a specific guide.
|
| Each guide: symbol (emoji shown as the visual cue), steps[], mistakes[].
|
*/

return [

    'aakash' => [
        'symbol' => '🌌',
        'steps' => [
            [
                'title' => 'Open Your Palm',
                'description' => 'Open your palm directly facing the camera with all five fingers straight, extended, and relaxed.',
            ],
            [
                'title' => 'Fold the Middle Finger',
                'description' => 'Slowly bend only the middle finger toward the thumb. Keep the index, ring, and little fingers straight.',
            ],
            [
                'title' => 'Touch the Thumb',
                'description' => 'Gently touch the tip of the middle finger to the tip of the thumb, making light contact and forming a circle.',
            ],
            [
                'title' => 'Straighten Remaining Fingers',
                'description' => 'Keep the index, ring, and little fingers fully straight and relaxed while maintaining the thumb-middle finger contact.',
            ],
            [
                'title' => 'Final Aakash Mudra',
                'description' => 'Hold the completed gesture steady in front of the camera with the palm facing directly towards the screen.',
            ],
        ],
        'step_images' => [
            'images/mudras/aakash_step1.jpg',
            'images/mudras/aakash_step2.jpg',
            'images/mudras/aakash_step3.jpg',
            'images/mudras/aakash_step4.jpg',
            'images/mudras/aakash_step5.jpg',
        ],
        'tips' => [
            'Keep your hand positioned within the camera frame.',
            'Apply only gentle finger contact; avoid pressing forcefully.',
            'Keep the non-touching fingers fully straight.',
            'Hold the hand steady and avoid trembling.',
        ],
        'mistakes' => [
            'Pressing the thumb and middle finger too hard.',
            'Curling or bending the index, ring, or little fingers.',
            'Moving the hand too fast or holding it out of frame.',
        ],
        'before_start' => [
            'Ensure the camera lens is clean and well-lit.',
            'Position your hand approximately 1–2 feet away from the webcam.',
            'Remove any rings or wrist accessories that might block visibility.',
        ],
        'duration' => 'Hold the final gesture steadily for your prescribed duration to complete the verification session. Brief slips only pause the timer — it resumes when the mudra is correct again.',
    ],

    'default' => [
        'symbol' => '🧘',
        'steps' => [
            "Follow your doctor's guidance for this mudra.",
            'Form the gesture and hold it steadily.',
            'Keep your hand within the camera view.',
            'Breathe normally and stay relaxed.',
        ],
        'mistakes' => [
            'Moving the hand too quickly',
            'Holding the gesture out of frame',
            'Tensing up instead of staying relaxed',
        ],
    ],

    'mushti' => [
        'symbol' => '✊',
        'steps' => [
            'Fold all four fingers inward into a fist.',
            'Place your thumb gently on top of the fingers.',
            'Keep the fist firm but relaxed.',
            'Hold steady and breathe normally.',
        ],
        'mistakes' => [
            'Thumb tucked inside the fist',
            'Fingers not fully closed',
            'Loose or shaking fist',
        ],
    ],

    'shikhar' => [
        'symbol' => '✊',
        'steps' => [
            'Make a closed fist.',
            'Face the fist towards the camera, about an arm’s length away.',
            'Keep the fist firm but relaxed.',
            'Hold steady until the bar fills.',
        ],
        'mistakes' => [
            'Fist loose or shaking',
            'Hand too far away or out of frame',
            'Fist turned away from the camera',
        ],
    ],

    'pataka' => [
        'symbol' => '✋',
        'steps' => [
            'Open your palm with fingers together and straight.',
            'Bend the thumb slightly inward.',
            'Keep the hand flat and upright.',
            'Hold steady within the frame.',
        ],
        'mistakes' => [
            'Fingers spread apart',
            'Thumb sticking out',
            'Hand tilted away from the camera',
        ],
    ],

    'tripataka' => [
        'symbol' => '🖐️',
        'steps' => [
            'Start from Pataka (flat palm, fingers together).',
            'Bend only the ring finger inward.',
            'Keep the other fingers straight.',
            'Hold steady.',
        ],
        'mistakes' => ['Bending the wrong finger', 'Fingers drifting apart', 'Hand not steady'],
    ],

    'ardhpataka' => [
        'symbol' => '🖖',
        'steps' => [
            'From Tripataka, also bend the little finger inward.',
            'Keep the index and middle fingers straight.',
            'Hold the shape steadily.',
        ],
        'mistakes' => ['Extra fingers extended', 'Shape collapsing', 'Hand out of frame'],
    ],

    'kartarimukh' => [
        'symbol' => '✌️',
        'steps' => [
            'Stretch the index and middle fingers apart.',
            'Fold the ring and little fingers down.',
            'Hold the “scissors” shape steady.',
        ],
        'mistakes' => ['Fingers too close together', 'Folded fingers popping up', 'Shaking hand'],
    ],

    'mayur' => [
        'symbol' => '🤏',
        'steps' => [
            'Touch the tip of the ring finger to the thumb tip.',
            'Keep the other fingers extended.',
            'Hold gently and steadily.',
        ],
        'mistakes' => ['Wrong finger touching the thumb', 'Other fingers folded', 'Grip too tense'],
    ],

    'aral' => [
        'symbol' => '🤙',
        'steps' => [
            'Curve the index finger.',
            'Keep the other fingers slightly bent.',
            'Hold the relaxed curve steady.',
        ],
        'mistakes' => ['Fingers fully straight', 'Hand too tense', 'Out of frame'],
    ],

    'ardhachandra' => [
        'symbol' => '🌙',
        'steps' => [
            'Open the palm into a crescent shape.',
            'Stretch the thumb out to the side.',
            'Keep fingers together and steady.',
        ],
        'mistakes' => ['Thumb not stretched out', 'Fingers spread apart', 'Hand tilted'],
    ],

    'shuktund' => [
        'symbol' => '🖐️',
        'steps' => [
            'Open your hand fully.',
            'Spread all five fingers apart.',
            'Face your open palm towards the camera.',
            'Hold steady until the bar fills.',
        ],
        'mistakes' => ['Fingers held together', 'Hand turned sideways', 'Hand too far / out of frame'],
    ],

    'soochi' => [
        'symbol' => '☝️',
        'steps' => [
            'Point the index finger straight up.',
            'Fold the middle, ring and little fingers into the palm.',
            'Rest the thumb against the folded fingers.',
            'Face the hand to the camera and hold steady.',
        ],
        'mistakes' => ['More than one finger raised', 'Index finger bent', 'Hand out of frame'],
    ],

    'trishool' => [
        'symbol' => '🤟',
        'steps' => [
            'Raise the index, middle and ring fingers like a trident.',
            'Fold the thumb and little finger down.',
            'Keep the three fingers straight and slightly apart.',
            'Face the hand to the camera and hold steady.',
        ],
        'mistakes' => ['Little finger sticking up', 'Fingers bent', 'Hand tilted away'],
    ],

];
