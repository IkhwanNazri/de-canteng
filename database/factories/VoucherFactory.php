<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\VoucherScope;
use App\Models\Shop;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'code' => Str::upper(Str::random(8)),
            'name' => 'Baucar Kedai',
            'scope' => VoucherScope::Shop,
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 10,
            'min_spend' => 50,
            'is_active' => true,
        ];
    }

    /**
     * @param  'shop'|'product'|'follower'|'live'|'platform'  $scope
     */
    public function scoped(string $scope): static
    {
        return $this->state(fn () => ['scope' => VoucherScope::from($scope)]);
    }
}
