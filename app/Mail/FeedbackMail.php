<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FeedbackMail extends Mailable
{
    /**
     * @param  array<int, UploadedFile>  $screenshots
     */
    public function __construct(
        public User $user,
        public string $feedbackMessage,
        public array $screenshots,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Обратная связь от {$this->user->name} ({$this->user->email})",
            replyTo: [
                new Address($this->user->email, $this->user->name),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.feedback',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return collect($this->screenshots)
            ->map(function (UploadedFile $file): Attachment {
                $filename = $file->getClientOriginalName() ?: 'screenshot.png';

                return Attachment::fromData(
                    fn () => $file->get(),
                    $filename,
                )->withMime($file->getMimeType() ?: 'application/octet-stream');
            })
            ->all();
    }
}
