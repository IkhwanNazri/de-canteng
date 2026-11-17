<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'number', 'payment_id', 'user_id', 'shop_id', 'status', 'subtotal', 'shipping_fee', 'total',
        'commission', 'seller_amount', 'recipient_name', 'phone', 'address', 'postcode', 'city', 'state',
        'tracking_number', 'paid_at', 'shipped_at',
    ];

    protected $appends = ['status_label'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'float',
            'shipping_fee' => 'float',
            'total' => 'float',
            'commission' => 'float',
            'seller_amount' => 'float',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    public function getStatusLabelAttribute(): ?string
    {
        return $this->status?->label();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
