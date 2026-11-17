<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
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
        $mail = (new MailMessage)
            ->subject("Pesanan baharu — {$this->order->number}")
            ->greeting("Tahniah {$notifiable->name}!")
            ->line("Kedai {$this->order->shop->name} menerima pesanan baharu yang sudah dibayar:");

        foreach ($this->order->items as $item) {
            $variant = collect([$item->size, $item->color])->filter()->implode(' / ');
            $mail->line("• {$item->quantity}× {$item->product_name}".($variant ? " ({$variant})" : ''));
        }

        return $mail
            ->line('Anda akan terima: RM'.number_format($this->order->seller_amount, 2).' (selepas caj platform RM'.number_format($this->order->commission, 2).')')
            ->action('Proses Pesanan', route('seller.orders.index', ['status' => 'paid']));
    }
}
