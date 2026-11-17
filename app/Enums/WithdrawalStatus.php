<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Kelulusan',
            self::Paid => 'Sudah Dibayar',
            self::Rejected => 'Ditolak',
        };
    }
}
