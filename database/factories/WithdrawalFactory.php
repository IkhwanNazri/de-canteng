<?php

namespace Database\Factories;

use App\Enums\WithdrawalStatus;
use App\Models\Shop;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Withdrawal>
 */
class WithdrawalFactory extends Factory
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
            'amount' => 50,
            'status' => WithdrawalStatus::Pending,
            'bank_name' => 'Maybank',
            'bank_account' => '164000000000',
        ];
    }
}
