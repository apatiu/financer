<?php

namespace Database\Factories;

use App\Enums\InsuranceStatus;
use App\Enums\InsuranceType;
use App\Enums\PremiumFrequency;
use App\Models\InsurancePolicy;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsurancePolicy>
 */
class InsurancePolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'name' => fake()->words(3, true), 'insurer' => fake()->company(), 'type' => InsuranceType::Endowment, 'sum_assured' => 100000000, 'premium_amount' => 5000000, 'premium_frequency' => PremiumFrequency::Yearly, 'status' => InsuranceStatus::Active];
    }
}
