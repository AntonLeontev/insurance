<?php

namespace App\Notifications;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UserInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $temporalPassword, public int $agencyId) {}

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
        $agency = Agency::find($this->agencyId);

        return (new MailMessage)
            ->subject('Приглашение в агентство')
            ->line("Вы приглашены в агентство {$agency->name}.")
            ->when(! empty($this->temporalPassword), function (MailMessage $mailMessage) use ($notifiable) {
                return $mailMessage->action('Присоединиться', route('password.create', [$notifiable->email, $this->temporalPassword]));
            })
            ->when(empty($this->temporalPassword), function (MailMessage $mailMessage) {
                return $mailMessage->action('Присоединиться', URL::to('/'));
            });
    }
}
