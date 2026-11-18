<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserVoucher>
 */
class UserVoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'voucher_id' => Voucher::factory()];
    }
}
