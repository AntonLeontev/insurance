<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $temporalPassword) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $notifiable->load('agency');

        return (new MailMessage)
            ->subject('Приглашение в агентство')
            ->line("Вы приглашены в агентство {$notifiable->agency->name}.")
            ->action('Присоединиться', route('password.create', [$notifiable->email, $this->temporalPassword]));
    }
}
