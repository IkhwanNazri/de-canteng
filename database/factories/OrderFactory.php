<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'DBT-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'user_id' => User::factory(),
            'shop_id' => Shop::factory(),
            'status' => OrderStatus::Paid,
            'subtotal' => 100,
            'shipping_fee' => 8,
            'total' => 108,
            'recipient_name' => fake()->name(),
            'phone' => '0123456789',
            'address' => fake()->streetAddress(),
            'postcode' => '50000',
            'city' => 'Kuala Lumpur',
            'state' => 'W.P. Kuala Lumpur',
        ];
    }
}
