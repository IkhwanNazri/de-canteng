<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaidNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Bayaran diterima — {$this->order->number}")
            ->greeting("Hai {$notifiable->name},")
            ->line("Terima kasih! Bayaran untuk pesanan {$this->order->number} daripada {$this->order->shop->name} telah diterima.")
            ->line('Jumlah: RM'.number_format($this->order->total, 2))
            ->action('Lihat Pesanan', route('orders.show', $this->order->number))
            ->line('Kami akan maklumkan bila pesanan dihantar.');
    }
}
