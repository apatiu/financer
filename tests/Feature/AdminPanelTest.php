<?php

use App\Enums\FixedAssetType;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\FixedAssets\Pages\CreateFixedAsset;
use App\Filament\Resources\FixedAssets\Pages\EditFixedAsset;
use App\Filament\Resources\FixedAssets\RelationManagers\ValuationsRelationManager;
use App\Filament\Widgets\NetWorthOverview;
use App\Models\Account;
use App\Models\Asset;
use App\Models\FixedAsset;
use App\Models\User;
use App\Models\Workspace;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = $this->user->workspaces()->sole();

    $this->actingAs($this->user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($this->workspace);
    Filament::bootCurrentPanel();
});

it('renders every resource page for the current workspace', function (string $path) {
    $this->get("/admin/{$this->workspace->id}/{$path}")->assertSuccessful();
})->with([
    'dashboard' => '',
    'accounts' => 'accounts',
    'accounts create' => 'accounts/create',
    'categories' => 'categories',
    'transactions' => 'transactions',
    'transactions create' => 'transactions/create',
    'fixed assets' => 'fixed-assets',
    'fixed assets create' => 'fixed-assets/create',
    'insurance' => 'insurance-policies',
    'insurance create' => 'insurance-policies/create',
    'liabilities' => 'liabilities',
    'assets' => 'assets',
    'asset trades' => 'asset-trades',
    'asset trades create' => 'asset-trades/create',
    'holdings' => 'holdings',
    'exchange rates' => 'exchange-rates',
]);

it('forbids opening another user\'s workspace', function () {
    $other = User::factory()->create()->workspaces()->sole();

    $this->get("/admin/{$other->id}/accounts")->assertNotFound();
});

it('only lists accounts of the current workspace', function () {
    $mine = Account::factory()->for($this->workspace)->create();
    $theirs = Account::withoutEvents(fn () => Account::factory()->for(Workspace::factory()->create())->create());

    expect($theirs->workspace_id)->not->toBe($this->workspace->id);

    Livewire::test(ListAccounts::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);
});

it('stores money entered in baht as satang', function () {
    Livewire::test(CreateFixedAsset::class)
        ->fillForm([
            'type' => FixedAssetType::Land->value,
            'name' => 'ที่ดินโฉนด 12345',
            'ownership_percent' => 50,
            'currency' => 'thb',
            'purchase_price' => 1500000.50,
            'status' => 'owned',
            'owner_name' => 'พ่อ',
            'include_in_net_worth' => false,
            'details' => ['deed_number' => '12345', 'rai' => 2],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $asset = FixedAsset::sole();

    expect($asset->purchase_price)->toBe(150000050)
        ->and($asset->currency)->toBe('THB')
        ->and($asset->workspace_id)->toBe($this->workspace->id)
        ->and($asset->owner_name)->toBe('พ่อ')
        ->and($asset->include_in_net_worth)->toBeFalse()
        ->and($asset->details['deed_number'])->toBe('12345');
});

it('shows stored satang as baht when editing', function () {
    $asset = FixedAsset::factory()->for($this->workspace)->create(['purchase_price' => 150000050]);

    Livewire::test(EditFixedAsset::class, ['record' => $asset->getKey()])
        ->assertSchemaStateSet(['purchase_price' => 1500000.5]);
});

it('manages valuations from the fixed asset page', function () {
    $asset = FixedAsset::factory()->for($this->workspace)->create();

    Livewire::test(ValuationsRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => EditFixedAsset::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'as_of_date' => now()->toDateString(),
            'value' => 2000000,
            'method' => 'appraisal',
        ])
        ->assertHasNoFormErrors();

    expect($asset->valuations()->sole()->value)->toBe(200000000);
});

it('shows shared assets but never another workspace\'s assets', function () {
    $shared = Asset::factory()->create(['symbol' => 'SHARED']);
    $mine = Asset::factory()->create(['symbol' => 'MINE', 'workspace_id' => $this->workspace->id]);
    $theirs = Asset::factory()->create(['symbol' => 'THEIRS', 'workspace_id' => Workspace::factory()->create()->id]);

    $ids = AssetResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($shared->id, $mine->id)->not->toContain($theirs->id)
        ->and(AssetResource::canEdit($shared))->toBeFalse()
        ->and(AssetResource::canEdit($mine))->toBeTrue();
});

it('renders the net worth widget', function () {
    Account::factory()->for($this->workspace)->create(['opening_balance' => 12345600]);

    Livewire::test(NetWorthOverview::class)->assertSee('123,456 THB');
});
