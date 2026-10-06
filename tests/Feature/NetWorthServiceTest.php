<?php

use App\Enums\AccountType;
use App\Enums\FixedAssetStatus;
use App\Enums\InsuranceStatus;
use App\Enums\TradeType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetPrice;
use App\Models\AssetTrade;
use App\Models\ExchangeRate;
use App\Models\FixedAsset;
use App\Models\InsurancePolicy;
use App\Models\InsurancePolicyValue;
use App\Models\Liability;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Services\NetWorthService;

function summary(Workspace $workspace): array
{
    return app(NetWorthService::class)->summarize($workspace);
}

test('an empty workspace has zero net worth', function () {
    expect(summary(Workspace::factory()->create())['net_worth'])->toBe(0);
});

test('account balances include opening balance and transactions', function () {
    $workspace = Workspace::factory()->create();
    $bank = Account::factory()->for($workspace)->create(['opening_balance' => 100000]);
    Transaction::factory()->for($workspace)->create(['account_id' => $bank->id, 'amount' => 50000]);
    Transaction::factory()->for($workspace)->create(['account_id' => $bank->id, 'amount' => -30000]);

    expect(summary($workspace)['cash'])->toBe(120000);
});

test('credit card balances count as debt and archived accounts are ignored', function () {
    $workspace = Workspace::factory()->create();
    Account::factory()->for($workspace)->create(['type' => AccountType::CreditCard, 'opening_balance' => -250000]);
    Account::factory()->for($workspace)->create(['opening_balance' => 999999, 'is_archived' => true]);

    $result = summary($workspace);

    expect($result['credit_card_debt'])->toBe(250000)
        ->and($result['cash'])->toBe(0)
        ->and($result['net_worth'])->toBe(-250000);
});

test('holdings are valued at the latest price in satang', function () {
    $workspace = Workspace::factory()->create();
    $account = Account::factory()->for($workspace)->create(['type' => AccountType::Brokerage]);
    $asset = Asset::factory()->create();
    AssetTrade::factory()->create([
        'workspace_id' => $workspace->id, 'account_id' => $account->id, 'asset_id' => $asset->id,
        'type' => TradeType::Buy, 'quantity' => '100', 'amount' => -100000,
    ]);
    AssetPrice::factory()->for($asset)->create(['date' => now()->subDays(2), 'price' => '10']);
    AssetPrice::factory()->for($asset)->create(['date' => now()->subDay(), 'price' => '12.50']);

    expect(summary($workspace)['investments'])->toBe(125000);
});

test('holdings priced in another currency are converted with the latest rate', function () {
    $workspace = Workspace::factory()->create();
    ExchangeRate::factory()->for($workspace)->create(['currency' => 'USD', 'date' => now()->subDays(5), 'rate' => '30']);
    ExchangeRate::factory()->for($workspace)->create(['currency' => 'USD', 'date' => now()->subDay(), 'rate' => '35']);
    $account = Account::factory()->for($workspace)->create(['type' => AccountType::Brokerage]);
    $asset = Asset::factory()->create(['currency' => 'USD']);
    AssetTrade::factory()->create([
        'workspace_id' => $workspace->id, 'account_id' => $account->id, 'asset_id' => $asset->id,
        'type' => TradeType::Buy, 'quantity' => '2', 'amount' => -100000,
    ]);
    AssetPrice::factory()->for($asset)->create(['date' => now()->subDay(), 'price' => '100']);

    $result = summary($workspace);

    expect($result['investments'])->toBe(700000)
        ->and($result['unconverted_items'])->toBe(0);
});

test('items without an exchange rate are reported, not counted', function () {
    $workspace = Workspace::factory()->create();
    $account = Account::factory()->for($workspace)->create(['type' => AccountType::Brokerage]);
    $asset = Asset::factory()->create(['currency' => 'USD']);
    AssetTrade::factory()->create([
        'workspace_id' => $workspace->id, 'account_id' => $account->id, 'asset_id' => $asset->id,
        'type' => TradeType::Buy, 'quantity' => '1', 'amount' => -100000,
    ]);
    AssetPrice::factory()->for($asset)->create(['date' => now()->subDay(), 'price' => '100']);

    $result = summary($workspace);

    expect($result['investments'])->toBe(0)
        ->and($result['unconverted_items'])->toBe(1);
});

test('insurance counts cash value only for in-force policies', function () {
    $workspace = Workspace::factory()->create();
    $active = InsurancePolicy::factory()->for($workspace)->create(['sum_assured' => 5000000000]);
    InsurancePolicyValue::factory()->for($active, 'insurancePolicy')->create(['as_of_date' => now()->subYear(), 'cash_value' => 100000]);
    InsurancePolicyValue::factory()->for($active, 'insurancePolicy')->create(['as_of_date' => now()->subDay(), 'cash_value' => 200000]);
    InsurancePolicyValue::factory()->for($active, 'insurancePolicy')->create(['as_of_date' => now()->addYear(), 'cash_value' => 900000]);

    $lapsed = InsurancePolicy::factory()->for($workspace)->create(['status' => InsuranceStatus::Lapsed]);
    InsurancePolicyValue::factory()->for($lapsed, 'insurancePolicy')->create(['as_of_date' => now()->subDay(), 'cash_value' => 777777]);

    expect(summary($workspace)['insurance'])->toBe(200000);
});

test('fixed assets use ownership share and skip sold or excluded ones', function () {
    $workspace = Workspace::factory()->create();
    FixedAsset::factory()->for($workspace)->create(['purchase_price' => 1000000, 'ownership_percent' => 50]);
    FixedAsset::factory()->for($workspace)->create(['purchase_price' => 5000000, 'status' => FixedAssetStatus::Sold]);
    FixedAsset::factory()->for($workspace)->create(['purchase_price' => 7000000, 'include_in_net_worth' => false]);

    expect(summary($workspace)['fixed_assets'])->toBe(500000);
});

test('active loans reduce net worth', function () {
    $workspace = Workspace::factory()->create();
    FixedAsset::factory()->for($workspace)->create(['purchase_price' => 1000000]);
    Liability::factory()->for($workspace)->create(['outstanding_balance' => 400000]);
    Liability::factory()->for($workspace)->create(['outstanding_balance' => 999999, 'status' => 'closed']);

    $result = summary($workspace);

    expect($result['loans'])->toBe(400000)
        ->and($result['net_worth'])->toBe(600000);
});

test('other workspaces are never mixed in', function () {
    $mine = Workspace::factory()->create();
    $theirs = Workspace::factory()->create();
    Account::factory()->for($theirs)->create(['opening_balance' => 123456]);

    expect(summary($mine)['net_worth'])->toBe(0);
});

test('foreign currency accounts, loans and fixed assets are converted', function () {
    $workspace = Workspace::factory()->create();
    ExchangeRate::factory()->for($workspace)->create(['currency' => 'USD', 'date' => now()->subDay(), 'rate' => '35']);
    Account::factory()->for($workspace)->create(['currency' => 'USD', 'opening_balance' => 10000]);
    FixedAsset::factory()->for($workspace)->create(['currency' => 'USD', 'purchase_price' => 100000]);
    Liability::factory()->for($workspace)->create(['currency' => 'USD', 'outstanding_balance' => 20000]);

    $result = summary($workspace);

    expect($result['cash'])->toBe(350000)
        ->and($result['fixed_assets'])->toBe(3500000)
        ->and($result['loans'])->toBe(700000);
});

test('items owned by family members are skipped when excluded', function () {
    $workspace = Workspace::factory()->create();
    $account = Account::factory()->for($workspace)->create(['opening_balance' => 100000, 'owner_name' => 'พ่อ', 'include_in_net_worth' => false]);
    $asset = Asset::factory()->create();
    AssetTrade::factory()->create([
        'workspace_id' => $workspace->id, 'account_id' => $account->id, 'asset_id' => $asset->id,
        'type' => TradeType::Buy, 'quantity' => '100', 'amount' => -100000,
    ]);
    AssetPrice::factory()->for($asset)->create(['date' => now()->subDay(), 'price' => '10']);
    InsurancePolicy::factory()->for($workspace)->create(['owner_name' => 'แม่', 'include_in_net_worth' => false]);
    Liability::factory()->for($workspace)->create(['owner_name' => 'พ่อ', 'include_in_net_worth' => false]);

    $result = summary($workspace);

    expect($result['cash'])->toBe(0)
        ->and($result['investments'])->toBe(0)
        ->and($result['loans'])->toBe(0)
        ->and($result['net_worth'])->toBe(0);
});
