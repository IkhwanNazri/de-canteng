<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Batik '.fake()->unique()->lastName();

        return [
            'user_id' => User::factory()->seller(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'rating' => fake()->randomFloat(1, 4, 5),
            'followers_count' => fake()->numberBetween(0, 20000),
            'is_verified' => true,
        ];
    }
}
