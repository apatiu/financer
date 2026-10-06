<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'account_id' => fn (array $attributes) => Account::factory()->create(['workspace_id' => $attributes['workspace_id']])->id, 'date' => fake()->date(), 'amount' => fake()->numberBetween(-500000, 500000), 'description' => fake()->sentence(3)];
    }
}
