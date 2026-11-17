<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification
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
            ->subject("Pesanan dihantar — {$this->order->number}")
            ->greeting("Hai {$notifiable->name},")
            ->line("Pesanan {$this->order->number} daripada {$this->order->shop->name} sudah dihantar.")
            ->line("No. tracking: {$this->order->tracking_number}")
            ->action('Lihat Pesanan', route('orders.show', $this->order->number))
            ->line('Bila barang sampai, tekan "Saya Dah Terima" dan beri ulasan ya!');
    }
}
