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

        /*
        | Internal mapping layer: raw YOLO class -> Siddha mudra label
        | (mudras.ai_class_label). The model's internal class vocabulary is an
        | implementation detail and must never surface in the UI, API, or
        | database — only the Siddha labels on the right-hand side exist there.
        | Any model output NOT listed here is treated as an incorrect mudra.
        */
        'label_map' => [
            // The Aakash Mudra pose (fingertip-to-thumb pinch, other fingers
            // extended) corresponds to this internal model class.
            'mayur' => 'aakash',
        ],
    ],

];
