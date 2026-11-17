<?php

namespace Database\Factories;

use App\Models\LiveComment;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveComment>
 */
class LiveCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'live_session_id' => LiveSession::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
            'is_auto_reply' => false,
        ];
    }
}
