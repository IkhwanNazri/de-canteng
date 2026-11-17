<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Database\Factories\WithdrawalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    /** @use HasFactory<WithdrawalFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id', 'amount', 'status', 'bank_name', 'bank_account', 'reference', 'admin_note', 'processed_by', 'processed_at',
    ];

    protected $appends = ['status_label'];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'amount' => 'float',
            'processed_at' => 'datetime',
        ];
    }

    public function getStatusLabelAttribute(): ?string
    {
        return $this->status?->label();
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
