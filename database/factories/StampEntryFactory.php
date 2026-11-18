<?php

namespace Database\Factories;

use App\Models\StampEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StampEntry>
 */
class StampEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'stamps' => 1, 'reason' => 'Pesanan'];
    }
}
