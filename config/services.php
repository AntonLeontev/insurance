<?php

return [
    'atol' => [
        'base_url' => env('ATOL_BASE_URL'),
    ],

    'telegram_logger' => [
        'token' => env('TELEGRAM_LOGGER_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_LOGGER_CHAT_ID'),
    ],

    'feedback' => [
        'email' => env('FEEDBACK_EMAIL', 'aner-anton@yandex.ru'),
        'max_files' => 5,
        'max_file_kb' => 5120,
        'max_message_length' => 1000,
    ],
];
