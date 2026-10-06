<?php

use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Services\NetWorthService;
use App\Services\TransferService;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->bank = Account::factory()->for($this->workspace)->create(['name' => 'ออมทรัพย์', 'opening_balance' => 1000000]);
    $this->cash = Account::factory()->for($this->workspace)->create(['name' => 'เงินสด']);
    $this->transfers = app(TransferService::class);
});

test('a transfer creates two linked legs and moves the balance', function () {
    ['out' => $out, 'in' => $in] = $this->transfers->transfer($this->bank, $this->cash, 250000, '2026-10-01');

    expect($out->amount)->toBe(-250000)
        ->and($in->amount)->toBe(250000)
        ->and($out->transfer_group_id)->toBe($in->transfer_group_id)
        ->and($out->account_id)->toBe($this->bank->id)
        ->and($in->account_id)->toBe($this->cash->id)
        ->and($this->bank->balance())->toBe(750000)
        ->and($this->cash->balance())->toBe(250000);
});

test('a transfer between own accounts does not change net worth', function () {
    $before = app(NetWorthService::class)->summarize($this->workspace)['net_worth'];

    $this->transfers->transfer($this->bank, $this->cash, 250000, '2026-10-01');

    expect(app(NetWorthService::class)->summarize($this->workspace)['net_worth'])->toBe($before);
});

test('a cross currency transfer uses the received amount', function () {
    $usd = Account::factory()->for($this->workspace)->create(['currency' => 'USD']);

    ['out' => $out, 'in' => $in] = $this->transfers->transfer($this->bank, $usd, 350000, '2026-10-01', receivedAmount: 9800);

    expect($out->amount)->toBe(-350000)->and($in->amount)->toBe(9800);
});

test('a cross currency transfer requires the received amount', function () {
    $usd = Account::factory()->for($this->workspace)->create(['currency' => 'USD']);

    expect(fn () => $this->transfers->transfer($this->bank, $usd, 350000, '2026-10-01'))
        ->toThrow(ValidationException::class);
    expect(Transaction::count())->toBe(0);
});

test('invalid transfers are rejected', function (string $case) {
    $other = Account::factory()->create();

    $attempt = match ($case) {
        'same account' => fn () => $this->transfers->transfer($this->bank, $this->bank, 100, '2026-10-01'),
        'zero amount' => fn () => $this->transfers->transfer($this->bank, $this->cash, 0, '2026-10-01'),
        'other workspace' => fn () => $this->transfers->transfer($this->bank, $other, 100, '2026-10-01'),
    };

    expect($attempt)->toThrow(ValidationException::class);
    expect(Transaction::count())->toBe(0);
})->with(['same account', 'zero amount', 'other workspace']);

test('deleting one leg removes the other and restoring brings both back', function () {
    ['out' => $out, 'in' => $in] = $this->transfers->transfer($this->bank, $this->cash, 250000, '2026-10-01');

    $out->delete();

    expect(Transaction::find($in->id))->toBeNull()
        ->and(Transaction::withTrashed()->find($in->id)->trashed())->toBeTrue()
        ->and($this->bank->balance())->toBe(1000000)
        ->and($this->cash->balance())->toBe(0);

    $out->restore();

    expect($this->bank->balance())->toBe(750000)
        ->and($this->cash->balance())->toBe(250000);
});

test('force deleting one leg force deletes the other', function () {
    ['out' => $out, 'in' => $in] = $this->transfers->transfer($this->bank, $this->cash, 250000, '2026-10-01');

    $in->forceDelete();

    expect(Transaction::withTrashed()->count())->toBe(0);
});

test('the transfer button on the transactions page moves money', function () {
    $user = User::factory()->create();
    $workspace = $user->workspaces()->sole();
    $bank = Account::factory()->for($workspace)->create(['opening_balance' => 1000000]);
    $cash = Account::factory()->for($workspace)->create();

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($workspace);
    Filament::bootCurrentPanel();

    Livewire::test(ListTransactions::class)
        ->callAction('transfer', [
            'from_account_id' => $bank->id,
            'to_account_id' => $cash->id,
            'date' => '2026-10-01',
            'amount' => 1500.25,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($bank->balance())->toBe(1000000 - 150025)
        ->and($cash->balance())->toBe(150025);
});

test('the transfer button cannot use accounts of another workspace', function () {
    $user = User::factory()->create();
    $workspace = $user->workspaces()->sole();
    $mine = Account::factory()->for($workspace)->create();
    $foreign = Account::withoutEvents(fn () => Account::factory()->create());

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($workspace);
    Filament::bootCurrentPanel();

    Livewire::test(ListTransactions::class)
        ->callAction('transfer', [
            'from_account_id' => $mine->id,
            'to_account_id' => $foreign->id,
            'date' => '2026-10-01',
            'amount' => 10,
        ])
        ->assertHasFormErrors(['to_account_id']);

    expect(Transaction::count())->toBe(0);
});
