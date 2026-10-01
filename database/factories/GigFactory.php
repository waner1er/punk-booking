<?php

namespace Database\Factories;

use App\Enums\DealType;
use App\Enums\GigStatus;
use App\Models\Gig;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gig>
 */
class GigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'date' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'status' => GigStatus::Prospect,
            'deal_type' => DealType::Fixed,
            'fee' => fake()->randomElement([150, 200, 300, 500]),
        ];
    }
}
