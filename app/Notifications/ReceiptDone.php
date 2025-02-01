<?php

namespace App\Notifications;

use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReceiptDone extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $receiptId) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $receipt = Receipt::find($this->receiptId);

        if ($receipt === null) {
            throw new \Exception('При отправке успешного уведомления не найден чек с ИД '.$this->receiptId);
        }

        return (new MailMessage)
            ->subject('Успешно оформлен чек')
            ->line(sprintf(
                'Чек за договор %s %s на сумму %s р успешно оформлен.',
                $receipt->contract_series,
                $receipt->contract_number,
                $receipt->amount,
            ))
            ->action('Посмотреть чек', url('/'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
