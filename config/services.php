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
        'url' => env('MEDIAPIPE_URL', 'http://127.0.0.1:8001'),
        'key' => env('MEDIAPIPE_API_KEY'),

        /*
        |----------------------------------------------------------------------
        | TEMPORARY POC MODEL MAPPING  (Bharatanatyam -> Siddha)
        |----------------------------------------------------------------------
        |
        | WHY THIS EXISTS: the current YOLO classifier was trained on a
        | Bharatanatyam/Kathak hand-gesture dataset as a temporary experiment,
        | so the model's internal class vocabulary uses Bharatanatyam names.
        | This application is a SIDDHA Mudra Therapy product and must expose
        | Siddha mudra names ONLY — in the UI, API responses, database and
        | logs. This map is the single translation point: raw model class
        | (left) -> Siddha label stored in mudras.ai_class_label (right).
        |
        | Any model output NOT listed here is reported as a generic
        | "incorrect mudra" and can never match a prescription. The raw model
        | class names must never be referenced anywhere else in the codebase.
        |
        | REMOVAL PLAN: delete this mapping (and use identity labels) once a
        | dedicated Siddha Mudra model is trained whose classes ARE the Siddha
        | labels themselves.
        */
        'temporary_poc_model_mapping' => [
            // Aakash Mudra (ஆகாய முத்திரை): fingertip-to-thumb pinch with the
            // other fingers extended — matches this internal model class.
            'mayur' => 'aakash',
        ],
    ],

];
