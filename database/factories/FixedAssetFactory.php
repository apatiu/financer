<?php

namespace Database\Factories;

use App\Enums\FixedAssetStatus;
use App\Enums\FixedAssetType;
use App\Models\FixedAsset;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedAsset>
 */
class FixedAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'type' => FixedAssetType::Land, 'name' => fake()->words(3, true), 'ownership_percent' => 100, 'purchase_price' => 100000000, 'status' => FixedAssetStatus::Owned];
    }
}
