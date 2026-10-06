<?php

use App\Enums\AccountType;
use App\Enums\TradeType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetTrade;
use App\Models\Holding;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Services\TradeService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->account = Account::factory()->for($this->workspace)->create(['type' => AccountType::Brokerage, 'opening_balance' => 10000000]);
    $this->asset = Asset::factory()->create(['symbol' => 'PTT']);
    $this->trades = app(TradeService::class);

    $this->buy = fn (string $quantity = '100', string $price = '35.50', int $fee = 0, bool $cash = true, string $date = '2026-10-01') => $this->trades->record(
        $this->account, $this->asset, TradeType::Buy, $date, $quantity, $price, $fee, recordCash: $cash,
    );
});

test('a buy stores positive units, a negative net amount and a paired cash transaction', function () {
    // 100 × 35.50 = 3,550.00 บาท + ค่าธรรมเนียม 25.00 บาท
    $trade = ($this->buy)('100', '35.50', 2500);

    expect($trade->type)->toBe(TradeType::Buy)
        ->and((float) $trade->quantity)->toBe(100.0)
        ->and($trade->amount)->toBe(-357500)
        ->and($trade->fee)->toBe(2500)
        ->and($trade->transaction->amount)->toBe(-357500)
        ->and($trade->transaction->description)->toBe('ซื้อ PTT 100')
        ->and($this->account->balance())->toBe(10000000 - 357500);
});

test('a buy updates the holding with its cost', function () {
    ($this->buy)('100', '35.50', 2500);

    $holding = Holding::sole();

    expect((float) $holding->quantity)->toBe(100.0)->and($holding->cost_basis)->toBe(357500);
});

test('a sell stores negative units, a positive net amount after fees and adds cash', function () {
    ($this->buy)('100', '35.50');

    $trade = $this->trades->record($this->account, $this->asset, TradeType::Sell, '2026-10-02', '40', '40.00', 1500);

    // 40 × 40.00 = 1,600.00 − 15.00
    expect((float) $trade->quantity)->toBe(-40.0)
        ->and($trade->amount)->toBe(158500)
        ->and((float) Holding::sole()->quantity)->toBe(60.0)
        ->and($this->account->balance())->toBe(10000000 - 355000 + 158500);
});

test('selling realizes gain against the average cost', function () {
    ($this->buy)('100', '35.50');

    $this->trades->record($this->account, $this->asset, TradeType::Sell, '2026-10-02', '40', '40.00', 1500);

    // ต้นทุน 40 หน่วย = 142,000 สตางค์ ขายได้สุทธิ 158,500
    expect(Holding::sole()->realized_gain)->toBe(16500);
});

test('selling more than the holding is rejected and nothing is saved', function () {
    ($this->buy)('100');

    expect(fn () => $this->trades->record($this->account, $this->asset, TradeType::Sell, '2026-10-02', '100.00000001', '40'))
        ->toThrow(ValidationException::class);

    expect(AssetTrade::count())->toBe(1)->and(Transaction::count())->toBe(1);
});

test('selling something that is not held is rejected', function () {
    expect(fn () => $this->trades->record($this->account, $this->asset, TradeType::Sell, '2026-10-02', '1', '40'))
        ->toThrow(ValidationException::class);
});

test('selling the whole holding is allowed', function () {
    ($this->buy)('100');

    $this->trades->record($this->account, $this->asset, TradeType::Sell, '2026-10-02', '100', '40');

    expect((float) Holding::sole()->quantity)->toBe(0.0)->and(Holding::sole()->cost_basis)->toBe(0);
});

test('the cash transaction is optional', function () {
    $trade = ($this->buy)('100', '35.50', 0, false);

    expect($trade->transaction_id)->toBeNull()
        ->and(Transaction::count())->toBe(0)
        ->and($this->account->balance())->toBe(10000000);
});

test('invalid input is rejected', function (string $quantity, string $price, int $fee) {
    expect(fn () => $this->trades->record($this->account, $this->asset, TradeType::Buy, '2026-10-01', $quantity, $price, $fee))
        ->toThrow(ValidationException::class);

    expect(AssetTrade::count())->toBe(0);
})->with([
    'zero quantity' => ['0', '10', 0],
    'negative quantity' => ['-5', '10', 0],
    'negative price' => ['5', '-10', 0],
    'negative fee' => ['5', '10', -1],
]);

test('only investment accounts can trade', function () {
    $bank = Account::factory()->for($this->workspace)->create(['type' => AccountType::Bank]);

    expect(fn () => $this->trades->record($bank, $this->asset, TradeType::Buy, '2026-10-01', '1', '10'))
        ->toThrow(ValidationException::class);
});

test('assets of another workspace cannot be traded', function () {
    $foreign = Asset::factory()->create(['workspace_id' => Workspace::factory()->create()->id]);

    expect(fn () => $this->trades->record($this->account, $foreign, TradeType::Buy, '2026-10-01', '1', '10'))
        ->toThrow(ValidationException::class);
});

test('a different asset currency needs the net amount in the account currency', function () {
    $usd = Asset::factory()->create(['currency' => 'USD']);

    expect(fn () => $this->trades->record($this->account, $usd, TradeType::Buy, '2026-10-01', '2', '100'))
        ->toThrow(ValidationException::class);

    $trade = $this->trades->record($this->account, $usd, TradeType::Buy, '2026-10-01', '2', '100', accountAmount: 710000);

    expect($trade->amount)->toBe(-710000)->and($trade->transaction->amount)->toBe(-710000);
});

test('fractional units and prices are handled precisely', function () {
    // กองทุน: 1,234.5678 หน่วย × 12.3456 = 15,241.48 บาท
    $trade = $this->trades->record($this->account, $this->asset, TradeType::Buy, '2026-10-01', '1234.5678', '12.3456');

    expect($trade->amount)->toBe(-1524148)
        ->and($trade->quantity)->toBe('1234.56780000');
});

test('deleting a trade removes its cash transaction and restoring brings both back', function () {
    $trade = ($this->buy)('100', '35.50');

    $trade->delete();

    expect(Transaction::count())->toBe(0)
        ->and($this->account->balance())->toBe(10000000)
        ->and(Holding::sole()->quantity)->toEqual(0);

    $trade->restore();

    expect(Transaction::count())->toBe(1)->and($this->account->balance())->toBe(10000000 - 355000);
});

test('force deleting a trade force deletes its cash transaction', function () {
    $trade = ($this->buy)('100', '35.50');

    $trade->forceDelete();

    expect(Transaction::withTrashed()->count())->toBe(0);
});

test('changing a trade amount updates its cash transaction', function () {
    $trade = ($this->buy)('100', '35.50');

    $trade->update(['amount' => -400000, 'date' => '2026-10-05']);

    expect($trade->transaction->fresh()->amount)->toBe(-400000)
        ->and($trade->transaction->fresh()->date->toDateString())->toBe('2026-10-05');
});
