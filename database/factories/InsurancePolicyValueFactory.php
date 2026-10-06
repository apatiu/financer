<?php

namespace Database\Factories;

use App\Models\InsurancePolicy;
use App\Models\InsurancePolicyValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsurancePolicyValue>
 */
class InsurancePolicyValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['insurance_policy_id' => InsurancePolicy::factory(), 'as_of_date' => fake()->unique()->date(), 'cash_value' => 10000000];
    }
}
