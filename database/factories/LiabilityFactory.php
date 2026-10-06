<?php

namespace Database\Factories;

use App\Enums\LiabilityType;
use App\Models\Liability;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Liability>
 */
class LiabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'name' => fake()->words(2, true), 'type' => LiabilityType::CarLoan, 'principal' => 50000000, 'outstanding_balance' => 40000000, 'status' => 'active'];
    }
}
