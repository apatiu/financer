<?php

namespace Database\Factories;

use App\Enums\TradeType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetTrade;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetTrade>
 */
class AssetTradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['workspace_id' => Workspace::factory(), 'account_id' => Account::factory(), 'asset_id' => Asset::factory(), 'date' => fake()->date(), 'type' => TradeType::Buy, 'quantity' => '100.00000000', 'price' => '10.00000000', 'fee' => 0, 'amount' => -100000];
    }
}
