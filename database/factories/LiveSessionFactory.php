<?php

namespace Database\Factories;

use App\Models\LiveSession;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSession>
 */
class LiveSessionFactory extends Factory
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
            'title' => 'Live '.fake()->words(3, true),
            'status' => 'scheduled',
            'auto_reply' => true,
        ];
    }

    /**
     * Indicate that the session is currently live.
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'live', 'started_at' => now()]);
    }
}
