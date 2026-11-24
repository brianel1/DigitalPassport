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

    'jooble' => [
        'key' => env('JOOBLE_API_KEY'),
        'url' => env('JOOBLE_API_URL', 'https://my.jooble.org/api'),
    ],

    'remotive' => [
        'url' => env('REMOTIVE_API_URL', 'https://remotive.com/api/remote-jobs'),
    ],

    'public_jobs' => [
        'sources' => ['hiredly', 'jobstore', 'ricebowl', 'maukerja', 'jobstreet'],
        'queries' => ['software', 'developer', 'it', 'data', 'internship', 'engineering', 'accounting', 'marketing', 'admin', 'customer service', 'sales', 'graduate'],
        'pages' => (int) env('PUBLIC_JOBS_PAGES', 3),
        'detail_limit' => (int) env('PUBLIC_JOBS_DETAIL_LIMIT', 150),
        // Public state pages (e.g. /jobsearch/jobs-in-melaka) give every state its own listings, not only Klang Valley.
        'regions' => ['johor', 'kedah', 'kelantan', 'melaka', 'negeri-sembilan', 'pahang', 'perak', 'perlis', 'pulau-pinang', 'sabah', 'sarawak', 'selangor', 'terengganu', 'kuala-lumpur', 'labuan', 'putrajaya'],
        'region_queries' => ['', 'software', 'internship'],
        'region_pages' => (int) env('PUBLIC_JOBS_REGION_PAGES', 2),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL'),
        'chat_model' => env('OLLAMA_CHAT_MODEL'),
        'timeout' => env('OLLAMA_TIMEOUT', 90),
        'keep_alive' => env('OLLAMA_KEEP_ALIVE', '30m'),
    ],

    'ocr' => ['python' => env('OCR_PYTHON')],

];
