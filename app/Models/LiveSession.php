<?php

namespace App\Models;

use Database\Factories\LiveSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveSession extends Model
{
    /** @use HasFactory<LiveSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id', 'title', 'status', 'cover', 'pinned_product_id', 'auto_reply',
        'viewers_count', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'auto_reply' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
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

    public function pinnedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'pinned_product_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LiveComment::class);
    }

    public function scopeLive(Builder $query): void
    {
        $query->where('status', 'live');
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    /**
     * The LiveKit room this session streams into.
     */
    public function roomName(): string
    {
        return "debatik-live-{$this->id}";
    }
}
