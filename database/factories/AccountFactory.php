<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'name' => fake()->words(2, true), 'type' => AccountType::Bank, 'institution' => fake()->company(), 'currency' => 'THB', 'opening_balance' => 0];
    }
}
