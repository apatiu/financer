<?php

namespace Database\Factories;

use App\Models\NetWorthSnapshot;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetWorthSnapshot>
 */
class NetWorthSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'date' => fake()->unique()->date(),
            'currency' => 'THB',
            'total_assets' => 1000000,
            'total_liabilities' => 200000,
            'net_worth' => 800000,
        ];
    }
}
