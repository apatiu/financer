<?php

namespace Database\Factories;

use App\Models\ExchangeRate;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'currency' => 'USD', 'date' => fake()->unique()->date(), 'rate' => '35.00000000'];
    }
}
