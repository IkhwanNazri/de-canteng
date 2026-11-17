<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id', 'category_id', 'name', 'slug', 'description', 'price', 'compare_price',
        'stock', 'sizes', 'colors', 'image', 'sold_count', 'rating', 'reviews_count', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'compare_price' => 'float',
            'rating' => 'float',
            'is_featured' => 'boolean',
            'sizes' => 'array',
            'colors' => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Path of an uploaded image on the public disk, or null for external URLs.
     */
    public function storedImagePath(): ?string
    {
        if (! $this->image || ! str_starts_with($this->image, '/storage/')) {
            return null;
        }

        return substr($this->image, strlen('/storage/'));
    }

    public function deleteStoredImage(): void
    {
        if ($path = $this->storedImagePath()) {
            Storage::disk('public')->delete($path);
        }
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Recalculate the cached rating and review count from real reviews.
     */
    public function refreshRating(): void
    {
        $this->update([
            'rating' => round((float) $this->reviews()->avg('rating'), 1),
            'reviews_count' => $this->reviews()->count(),
        ]);
    }
}
