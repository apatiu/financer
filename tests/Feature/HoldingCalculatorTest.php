<?php

use App\Enums\TradeType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetTrade;
use App\Models\Holding;
use App\Models\Workspace;

function trade(Account $account, Asset $asset, TradeType $type, string $date, string $quantity, int $amount): AssetTrade
{
    return AssetTrade::factory()->create([
        'workspace_id' => $account->workspace_id,
        'account_id' => $account->id,
        'asset_id' => $asset->id,
        'type' => $type,
        'date' => $date,
        'quantity' => $quantity,
        'amount' => $amount,
    ]);
}

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->account = Account::factory()->for($this->workspace)->create();
    $this->asset = Asset::factory()->create();
});

test('buying twice averages the cost', function () {
    trade($this->account, $this->asset, TradeType::Buy, '2026-01-01', '100', -100000);
    trade($this->account, $this->asset, TradeType::Buy, '2026-02-01', '100', -140000);

    $holding = Holding::sole();

    expect((float) $holding->quantity)->toBe(200.0)
        ->and($holding->cost_basis)->toBe(240000)
        ->and($holding->realized_gain)->toBe(0);
});

test('selling part of a holding realizes gain against average cost', function () {
    trade($this->account, $this->asset, TradeType::Buy, '2026-01-01', '100', -100000);
    trade($this->account, $this->asset, TradeType::Buy, '2026-02-01', '100', -140000);
    trade($this->account, $this->asset, TradeType::Sell, '2026-03-01', '-50', 80000);

    $holding = Holding::sole();

    expect((float) $holding->quantity)->toBe(150.0)
        ->and($holding->cost_basis)->toBe(180000)
        ->and($holding->realized_gain)->toBe(20000);
});

test('selling everything leaves no cost behind', function () {
    trade($this->account, $this->asset, TradeType::Buy, '2026-01-01', '3', -100000);
    trade($this->account, $this->asset, TradeType::Sell, '2026-01-02', '-1', 40000);
    trade($this->account, $this->asset, TradeType::Sell, '2026-01-03', '-2', 70000);

    $holding = Holding::sole();

    expect((float) $holding->quantity)->toBe(0.0)
        ->and($holding->cost_basis)->toBe(0)
        ->and($holding->realized_gain)->toBe(10000);
});

test('a split adds units without changing cost', function () {
    trade($this->account, $this->asset, TradeType::Buy, '2026-01-01', '100', -100000);
    trade($this->account, $this->asset, TradeType::Split, '2026-02-01', '100', 0);

    $holding = Holding::sole();

    expect((float) $holding->quantity)->toBe(200.0)
        ->and($holding->cost_basis)->toBe(100000);
});

test('deleting a trade recalculates the holding', function () {
    trade($this->account, $this->asset, TradeType::Buy, '2026-01-01', '100', -100000);
    $second = trade($this->account, $this->asset, TradeType::Buy, '2026-02-01', '100', -140000);

    $second->delete();

    expect((float) Holding::sole()->quantity)->toBe(100.0)
        ->and(Holding::sole()->cost_basis)->toBe(100000);
});

test('holdings are kept per account and asset', function () {
    $other = Account::factory()->for($this->workspace)->create();
    trade($this->account, $this->asset, TradeType::Buy, '2026-01-01', '10', -10000);
    trade($other, $this->asset, TradeType::Buy, '2026-01-01', '5', -5000);

    expect(Holding::count())->toBe(2);
});
