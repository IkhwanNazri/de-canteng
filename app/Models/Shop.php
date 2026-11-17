<?php

namespace App\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'slug', 'description', 'logo', 'banner',
        'rating', 'followers_count', 'is_verified', 'bank_name', 'bank_account',
    ];

    protected $hidden = ['bank_name', 'bank_account'];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'rating' => 'float'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function liveSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function walletBalance(): float
    {
        return (float) $this->walletTransactions()->sum('amount');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_follows')->withTimestamps();
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }
}
