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
    'hold_seconds' => (int) env('PRACTICE_HOLD_SECONDS', 3),

    // How often the browser samples a frame for detection (milliseconds).
    'detection_interval_ms' => (int) env('PRACTICE_DETECTION_INTERVAL_MS', 500),

    // Tolerance for gaps between matched frames before the hold restarts,
    // expressed as a multiple of the detection interval.
    'hold_grace_factor' => (float) env('PRACTICE_HOLD_GRACE_FACTOR', 2.5),

    // Time-to-live (seconds) for the cache-backed hold state of a session.
    'hold_cache_ttl' => (int) env('PRACTICE_HOLD_CACHE_TTL', 300),

    // Maximum accepted frame upload size (kilobytes).
    'max_image_kb' => (int) env('PRACTICE_MAX_IMAGE_KB', 2048),

    // JPEG quality used by the browser when encoding a frame (0–1).
    'jpeg_quality' => (float) env('PRACTICE_JPEG_QUALITY', 0.6),

    // Per-user rate limit for the detection endpoint (requests per minute).
    'detect_rate_limit_per_minute' => (int) env('PRACTICE_DETECT_RATE_LIMIT_PER_MINUTE', 120),

    // HTTP timeout (seconds) for a single inference request to the provider.
    'inference_timeout' => (int) env('PRACTICE_INFERENCE_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | History
    |--------------------------------------------------------------------------
    */

    // How many recent practice sessions the history page lists.
    'history_limit' => (int) env('PRACTICE_HISTORY_LIMIT', 20),

];
