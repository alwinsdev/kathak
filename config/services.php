<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'roboflow' => [
        'key' => env('ROBOFLOW_API_KEY'),
        'model_url' => env('ROBOFLOW_MODEL_URL'),
    ],

    /*
    | Which inference provider backs the practice workflow. 'roboflow' (default)
    | keeps the original behaviour; 'mediapipe' uses the self-hosted AI service.
    */
    'inference' => [
        'driver' => env('INFERENCE_DRIVER', 'roboflow'),
    ],

    'mediapipe' => [
        'url' => env('MEDIAPIPE_URL', 'http://localhost:8001'),
        'key' => env('MEDIAPIPE_API_KEY'),

        // Maps the AI service's labels to mudra classes (mudras.ai_class_label).
        // The rule-based engine emits hand-shape labels (open_palm/closed_fist);
        // the trained YOLO engine emits mudra tokens directly (identity mapping).
        'label_map' => [
            // rule-based engine
            'open_palm' => 'shuktund',
            'closed_fist' => 'shikhar',
            // YOLO engine (trained mudra classes)
            'shikhar' => 'shikhar',
            'shuktund' => 'shuktund',
            'pataka' => 'pataka',
            'mayur' => 'mayur',
            'soochi' => 'soochi',
            'trishool' => 'trishool',
        ],
    ],

];
