<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | AI verification defaults
    |--------------------------------------------------------------------------
    |
    | Defaults for the practice/verification flow. Individual prescriptions may
    | override the threshold and hold duration; these are the fallbacks.
    |
    */

    // Minimum detection confidence (0–1) for a frame to count as a match.
    'confidence_threshold' => (float) env('PRACTICE_CONFIDENCE_THRESHOLD', 0.75),

    // Seconds the correct mudra must be held continuously to verify a session.
    'hold_seconds' => (int) env('PRACTICE_HOLD_SECONDS', 5),

    // How often the browser samples a frame for detection (milliseconds).
    'detection_interval_ms' => (int) env('PRACTICE_DETECTION_INTERVAL_MS', 1000),

    // Maximum accepted frame upload size (kilobytes).
    'max_image_kb' => (int) env('PRACTICE_MAX_IMAGE_KB', 2048),

];
