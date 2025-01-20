<?php

return [
    'wamm' => [
        'token' => env('WAMM_TOKEN'),
    ],

    'openai' => [
        'token' => env('OPENAI_TOKEN'),
    ],

    'telegram_logger' => [
        'token' => env('TELEGRAM_LOGGER_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_LOGGER_CHAT_ID'),
    ],
];
