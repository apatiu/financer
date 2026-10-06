<?php

use App\Models\FixedAsset;
use App\Models\FixedAssetValuation;

test('current value falls back to the purchase price without valuations', function () {
    $asset = FixedAsset::factory()->create(['purchase_price' => 500000000]);

    expect($asset->currentValue())->toBe(500000000);
});

test('current value uses the latest valuation that is not in the future', function () {
    $asset = FixedAsset::factory()->create();
    FixedAssetValuation::factory()->for($asset)->create(['as_of_date' => now()->subYear(), 'value' => 600000000]);
    FixedAssetValuation::factory()->for($asset)->create(['as_of_date' => now()->subDay(), 'value' => 700000000]);
    FixedAssetValuation::factory()->for($asset)->create(['as_of_date' => now()->addYear(), 'value' => 900000000]);

    expect($asset->currentValue())->toBe(700000000);
});

test('owned value applies the ownership share', function () {
    $asset = FixedAsset::factory()->create(['purchase_price' => 1000000000, 'ownership_percent' => 25]);

    expect($asset->ownedValue())->toBe(250000000);
});
