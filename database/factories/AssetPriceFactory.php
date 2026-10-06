<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetPrice>
 */
class AssetPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['asset_id' => Asset::factory(), 'date' => fake()->unique()->date(), 'price' => '10.00000000', 'source' => 'manual'];
    }
}
