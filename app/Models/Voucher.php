<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\VoucherScope;
use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id', 'user_id', 'code', 'name', 'scope', 'discount_type', 'discount_value', 'max_discount',
        'min_spend', 'quantity', 'claimed_count', 'starts_at', 'ends_at', 'is_active',
    ];

    protected $appends = ['label', 'scope_label'];

    protected function casts(): array
    {
        return [
            'scope' => VoucherScope::class,
            'discount_type' => DiscountType::class,
            'discount_value' => 'float',
            'max_discount' => 'float',
            'min_spend' => 'float',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Active, within its date window and not sold out.
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->where(fn (Builder $q) => $q->whereNull('quantity')->orWhereColumn('claimed_count', '<', 'quantity'));
    }

    public function isPlatform(): bool
    {
        return $this->scope === VoucherScope::Platform;
    }

    public function isUsableNow(): bool
    {
        return $this->is_active
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->ends_at || $this->ends_at->isFuture());
    }

    /**
     * Discount (RM) for an eligible subtotal; 0 when the minimum spend is not met.
     */
    public function discountFor(float $eligibleSubtotal): float
    {
        if ($eligibleSubtotal <= 0 || $eligibleSubtotal < $this->min_spend) {
            return 0.0;
        }

        $discount = $this->discount_type === DiscountType::Percent
            ? $eligibleSubtotal * $this->discount_value / 100
            : $this->discount_value;

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return round(min($discount, $eligibleSubtotal), 2);
    }

    public function getLabelAttribute(): string
    {
        $value = $this->discount_type === DiscountType::Percent
            ? rtrim(rtrim(number_format($this->discount_value, 2), '0'), '.').'%'
            : 'RM'.rtrim(rtrim(number_format($this->discount_value, 2), '0'), '.');

        return "Diskaun {$value}";
    }

    public function getScopeLabelAttribute(): ?string
    {
        return $this->scope?->label();
    }
}
