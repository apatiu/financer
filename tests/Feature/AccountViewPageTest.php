<?php

use App\Enums\AccountType;
use App\Enums\TradeType;
use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Accounts\Pages\ViewAccount;
use App\Filament\Resources\Accounts\RelationManagers\AssetTradesRelationManager;
use App\Filament\Resources\Accounts\RelationManagers\HoldingsRelationManager;
use App\Filament\Resources\Accounts\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\AssetTrades\Pages\ListAssetTrades;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetTrade;
use App\Models\Holding;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TradeService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $user = User::factory()->create();
    $this->workspace = $user->workspaces()->sole();
    $this->broker = Account::factory()->for($this->workspace)->create(['name' => 'บัญชีหุ้น', 'type' => AccountType::Brokerage, 'opening_balance' => 10000000]);
    $this->bank = Account::factory()->for($this->workspace)->create(['name' => 'ออมทรัพย์', 'type' => AccountType::Bank]);
    $this->asset = Asset::factory()->create(['symbol' => 'PTT', 'name' => 'ปตท.']);

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($this->workspace);
    Filament::bootCurrentPanel();

    $this->tradesOf = fn (Account $account) => Livewire::test(AssetTradesRelationManager::class, [
        'ownerRecord' => $account, 'pageClass' => ViewAccount::class,
    ]);
});

test('clicking an account row opens its view page, not the edit form', function () {
    $table = Livewire::test(ListAccounts::class)->loadTable()->instance()->getTable();

    expect($table->getRecordUrl($this->broker))->toBe(AccountResource::getUrl('view', ['record' => $this->broker]));
});

test('the account page loads for every account type', function (AccountType $type) {
    $account = Account::factory()->for($this->workspace)->create(['type' => $type]);

    $this->get(AccountResource::getUrl('view', ['record' => $account]))->assertSuccessful()->assertSee($account->name);
})->with(AccountType::cases());

test('an investment account page has tabs for trades, holdings and cash transactions', function () {
    Livewire::test(ViewAccount::class, ['record' => $this->broker->getKey()])
        ->assertSeeLivewire(AssetTradesRelationManager::class);

    foreach ([AssetTradesRelationManager::class, HoldingsRelationManager::class, TransactionsRelationManager::class] as $manager) {
        expect($manager::canViewForRecord($this->broker, ViewAccount::class))->toBeTrue();
    }
});

test('a bank account page only shows cash transactions', function () {
    Livewire::test(ViewAccount::class, ['record' => $this->bank->getKey()])
        ->assertSeeLivewire(TransactionsRelationManager::class)
        ->assertDontSeeLivewire(AssetTradesRelationManager::class);

    expect(AssetTradesRelationManager::canViewForRecord($this->bank, ViewAccount::class))->toBeFalse()
        ->and(HoldingsRelationManager::canViewForRecord($this->bank, ViewAccount::class))->toBeFalse()
        ->and(TransactionsRelationManager::canViewForRecord($this->bank, ViewAccount::class))->toBeTrue();
});

test('the trades come first on an investment account page', function () {
    expect(array_slice(AccountResource::getRelations(), 0, 1))->toBe([AssetTradesRelationManager::class]);
});

test('the view page has an edit button that leads to the edit form', function () {
    Livewire::test(ViewAccount::class, ['record' => $this->broker->getKey()])
        ->assertActionExists('edit')
        ->assertActionHasLabel('edit', 'แก้ไขข้อมูลบัญชี')
        ->assertActionHasUrl('edit', AccountResource::getUrl('edit', ['record' => $this->broker]));
});

test('saving the edit form returns to the account page', function () {
    Livewire::test(EditAccount::class, ['record' => $this->broker->getKey()])
        ->fillForm(['name' => 'พอร์ตหลัก'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(AccountResource::getUrl('view', ['record' => $this->broker]));

    expect($this->broker->fresh()->name)->toBe('พอร์ตหลัก');
});

test('the buy button on the account page records a buy with its cash transaction', function () {
    ($this->tradesOf)($this->broker)
        ->callAction(TestAction::make('buy')->table(), [
            'asset_id' => $this->asset->id,
            'date' => '2026-10-01',
            'quantity' => 100,
            'price' => 35.5,
            'fee' => 25,
            'record_cash' => true,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('บันทึกการซื้อแล้ว');

    $trade = AssetTrade::sole();

    expect($trade->account_id)->toBe($this->broker->id)
        ->and($trade->type)->toBe(TradeType::Buy)
        ->and($trade->amount)->toBe(-357500)
        ->and($trade->created_by)->toBe(auth()->id())
        ->and($this->broker->balance())->toBe(10000000 - 357500);
});

test('the sell button records a sell and the cash comes in', function () {
    app(TradeService::class)->record($this->broker, $this->asset, TradeType::Buy, '2026-10-01', '100', '35.50');

    ($this->tradesOf)($this->broker)
        ->callAction(TestAction::make('sell')->table(), [
            'asset_id' => $this->asset->id,
            'date' => '2026-10-02',
            'quantity' => 40,
            'price' => 40,
            'fee' => 15,
            'record_cash' => true,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('บันทึกการขายแล้ว');

    expect(AssetTrade::where('type', TradeType::Sell)->sole()->amount)->toBe(158500)
        ->and((float) Holding::sole()->quantity)->toBe(60.0);
});

test('the sell button refuses to sell more than is held', function () {
    app(TradeService::class)->record($this->broker, $this->asset, TradeType::Buy, '2026-10-01', '100', '35.50');

    ($this->tradesOf)($this->broker)
        ->callAction(TestAction::make('sell')->table(), [
            'asset_id' => $this->asset->id, 'date' => '2026-10-02', 'quantity' => 150, 'price' => 40, 'fee' => 0, 'record_cash' => true,
        ])
        ->assertHasFormErrors(['quantity']);

    expect(AssetTrade::count())->toBe(1);
});

test('the sell button rejects an asset that is not held in the account', function () {
    $other = Asset::factory()->create(['symbol' => 'CPALL']);
    app(TradeService::class)->record($this->broker, $this->asset, TradeType::Buy, '2026-10-01', '100', '35.50');

    ($this->tradesOf)($this->broker)
        ->callAction(TestAction::make('sell')->table(), [
            'asset_id' => $other->id, 'date' => '2026-10-02', 'quantity' => 1, 'price' => 40, 'fee' => 0, 'record_cash' => false,
        ])
        ->assertHasFormErrors(['asset_id']);

    expect(AssetTrade::count())->toBe(1);
});

test('the trades list shows the buy and sell buttons and asks for the account', function () {
    Livewire::test(ListAssetTrades::class)
        ->assertActionExists('buy')
        ->assertActionExists('sell')
        ->callAction('buy', [
            'account_id' => $this->broker->id,
            'asset_id' => $this->asset->id,
            'date' => '2026-10-01',
            'quantity' => 10,
            'price' => 100,
            'fee' => 0,
            'record_cash' => false,
        ])
        ->assertHasNoFormErrors();

    $trade = AssetTrade::sole();

    expect($trade->account_id)->toBe($this->broker->id)
        ->and($trade->amount)->toBe(-100000)
        ->and(Transaction::count())->toBe(0);
});

test('the trades list cannot trade on a bank account', function () {
    Livewire::test(ListAssetTrades::class)
        ->callAction('buy', [
            'account_id' => $this->bank->id,
            'asset_id' => $this->asset->id,
            'date' => '2026-10-01', 'quantity' => 10, 'price' => 100, 'fee' => 0, 'record_cash' => false,
        ])
        ->assertHasFormErrors(['account_id']);

    expect(AssetTrade::count())->toBe(0);
});

test('the account trades table lists only that account and can delete a trade with its cash', function () {
    $mine = app(TradeService::class)->record($this->broker, $this->asset, TradeType::Buy, '2026-10-01', '10', '10');
    $otherBroker = Account::factory()->for($this->workspace)->create(['type' => AccountType::Brokerage]);
    $theirs = app(TradeService::class)->record($otherBroker, $this->asset, TradeType::Buy, '2026-10-01', '5', '10');

    ($this->tradesOf)($this->broker)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs])
        ->callAction(TestAction::make('delete')->table($mine));

    expect(AssetTrade::find($mine->id))->toBeNull()
        ->and(Transaction::count())->toBe(1)
        ->and(AssetTrade::find($theirs->id))->not->toBeNull();
});

test('holdings only list units that are still held', function () {
    app(TradeService::class)->record($this->broker, $this->asset, TradeType::Buy, '2026-10-01', '10', '10');
    $sold = Asset::factory()->create(['symbol' => 'OLD']);
    app(TradeService::class)->record($this->broker, $sold, TradeType::Buy, '2026-10-01', '5', '10');
    app(TradeService::class)->record($this->broker, $sold, TradeType::Sell, '2026-10-02', '5', '12');

    Livewire::test(HoldingsRelationManager::class, ['ownerRecord' => $this->broker, 'pageClass' => ViewAccount::class])
        ->loadTable()
        ->assertCountTableRecords(1)
        ->assertSee('PTT');
});
