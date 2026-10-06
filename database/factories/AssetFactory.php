<?php

namespace Database\Factories;

use App\Enums\AssetType;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['type' => AssetType::Stock, 'symbol' => fake()->unique()->lexify('????'), 'name' => fake()->company(), 'exchange' => 'SET', 'currency' => 'THB', 'unit' => 'share'];
    }
}
