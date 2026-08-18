<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $maxFiles = (int) config('services.feedback.max_files');
        $maxFileKb = (int) config('services.feedback.max_file_kb');
        $maxMessageLength = (int) config('services.feedback.max_message_length');

        return [
            'message' => ['required', 'string', "max:{$maxMessageLength}"],
            'screenshots' => ['nullable', 'array', "max:{$maxFiles}"],
            'screenshots.*' => ['file', 'image', 'mimes:png,jpg,jpeg,webp,gif', "max:{$maxFileKb}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'message' => 'сообщение',
            'screenshots' => 'скриншоты',
            'screenshots.*' => 'скриншот',
        ];
    }
}
