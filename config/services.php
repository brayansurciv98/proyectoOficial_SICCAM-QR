<?php

return [

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

    // Clave de la Raspberry Pi
    'raspberry' => [
        'token' => env('RASPBERRY_TOKEN', 'siccam_qr_secret_key_2026'),
        // Video en vivo de la Pi, p. ej. http://192.168.100.60:8080/video
                'stream_url' => env('RASPBERRY_STREAM_URL'),
    ],

    // Cuenta de servicio de Firebase (se descarga en la Parte 2)
    'firebase' => [
        'credenciales' => storage_path('app/private/firebase-credenciales.json'),
    ],

];