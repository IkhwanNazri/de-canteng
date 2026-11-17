<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = ['reference', 'user_id', 'amount', 'gateway', 'status', 'gateway_reference', 'payload', 'paid_at'];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'float',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Reference sent to the gateway as order_number (Bayarcash allows max 30 chars).
     */
    public function gatewayOrderNumber(): string
    {
        return 'DBTP'.$this->id;
    }

    public static function findByGatewayOrderNumber(string $orderNumber): ?self
    {
        if (! preg_match('/^DBTP(\d+)$/', $orderNumber, $matches)) {
            return null;
        }

        return self::find((int) $matches[1]);
    }
}
