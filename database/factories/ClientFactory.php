<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'age' => fake()->numberBetween(18, 70),
            'address' => fake()->address(),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'tel' => fake()->unique()->phoneNumber(),
            'skiing_level' => fake()->randomElement(['beginner', 'medium', 'confirmed']),
            'height' => fake()->randomFloat(2, 1.45, 2.05),
            'weight' => fake()->numberBetween(40, 120),
            'shoe_size' => fake()->numberBetween(30, 48),
            'password' => Hash::make('password123'),
            'role' => 'client',
        ];
    }
}
