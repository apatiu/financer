<?php

namespace Database\Factories;

use App\Enums\ValuationMethod;
use App\Models\FixedAsset;
use App\Models\FixedAssetValuation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedAssetValuation>
 */
class FixedAssetValuationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['fixed_asset_id' => FixedAsset::factory(), 'as_of_date' => fake()->unique()->date(), 'value' => 100000000, 'method' => ValuationMethod::Manual];
    }
}
