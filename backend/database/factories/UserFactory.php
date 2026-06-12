<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     * Pas de password : Clerk porte l'authentification.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clerk_id' => 'user_'.fake()->unique()->lexify('??????????'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'taxpayer_type' => 'individual',
            'commune_id' => null,
        ];
    }
}
