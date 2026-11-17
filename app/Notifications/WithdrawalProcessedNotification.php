<?php

namespace App\Notifications;

use App\Enums\WithdrawalStatus;
use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = 'RM'.number_format($this->withdrawal->amount, 2);
        $mail = (new MailMessage)->greeting("Hai {$notifiable->name},");

        if ($this->withdrawal->status === WithdrawalStatus::Paid) {
            return $mail->subject("Pengeluaran {$amount} telah dibayar")
                ->line("{$amount} telah dipindahkan ke {$this->withdrawal->bank_name} ({$this->withdrawal->bank_account}).")
                ->line("Rujukan: {$this->withdrawal->reference}")
                ->action('Lihat Pengeluaran', route('seller.withdrawals.index'));
        }

        return $mail->subject("Pengeluaran {$amount} ditolak")
            ->line("Permintaan pengeluaran {$amount} ditolak dan baki telah dikembalikan.")
            ->line("Sebab: {$this->withdrawal->admin_note}")
            ->action('Lihat Pengeluaran', route('seller.withdrawals.index'));
    }
}
