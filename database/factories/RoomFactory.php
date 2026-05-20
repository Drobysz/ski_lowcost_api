<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'num' => fake()->unique()->numberBetween(100, 699),
            'nb_lits' => fake()->randomElement([2, 4, 6]),
            'building_id' => fake()->numberBetween(1, 3),
            'floor' => fake()->numberBetween(0, 5),
            'surface' => fake()->numberBetween(18, 45),
            'view' => fake()->randomElement(['parking', 'mountains']),
            'balcony' => fake()->boolean(),
        ];
    }
}
