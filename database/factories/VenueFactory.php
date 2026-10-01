<?php

namespace Database\Factories;

use App\Enums\VenueType;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(VenueType::cases()),
            'city' => fake()->city(),
            'capacity' => fake()->numberBetween(30, 800),
        ];
    }
}
