<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Holding;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holding>
 */
class HoldingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'account_id' => Account::factory(), 'asset_id' => Asset::factory(), 'quantity' => '0', 'cost_basis' => 0, 'realized_gain' => 0];
    }
}
