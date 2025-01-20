<?php

use Illuminate\Support\Facades\Log;

if (! function_exists('telegram_info')) {
    function telegram_info(string $message, array $context = []): void
    {
        Log::channel('telegram')->info($message, $context);
    }
}
if (! function_exists('telegram_alert')) {
    function telegram_alert(string $message, array $context = []): void
    {
        Log::channel('telegram')->alert($message, $context);
    }
}
if (! function_exists('telegram_error')) {
    function telegram_error(string $message, array $context = []): void
    {
        Log::channel('telegram')->error($message, $context);
    }
}
