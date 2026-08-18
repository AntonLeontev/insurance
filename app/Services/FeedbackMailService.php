<?php

namespace App\Services;

use App\Mail\FeedbackMail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FeedbackMailService
{
    /**
     * @param  array<int, UploadedFile>  $screenshots
     */
    public function send(User $user, string $message, array $screenshots): void
    {
        try {
            Mail::to(config('services.feedback.email'))->send(
                new FeedbackMail($user, $message, $screenshots)
            );
        } catch (Throwable $exception) {
            Log::error('Не удалось отправить письмо обратной связи', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
