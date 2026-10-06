<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\NetWorthSnapshots\NetWorthSnapshotResource;
use App\Filament\Resources\NetWorthSnapshots\Pages\ListNetWorthSnapshots;
use App\Filament\Widgets\NetWorthTrend;
use App\Models\Account;
use App\Models\NetWorthSnapshot;
use App\Models\User;
use App\Models\Workspace;
use App\Services\NetWorthService;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(fn () => Carbon::setTestNow());

function actAsMember(): array
{
    $user = User::factory()->create();
    $workspace = $user->workspaces()->sole();

    test()->actingAs($user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($workspace);
    Filament::bootCurrentPanel();

    return [$user, $workspace];
}

test('a snapshot stores the current breakdown', function () {
    Carbon::setTestNow('2026-10-31 23:55:00');
    $workspace = Workspace::factory()->create();
    Account::factory()->for($workspace)->create(['opening_balance' => 500000]);

    $snapshot = app(NetWorthService::class)->snapshot($workspace);

    expect($snapshot->date->toDateString())->toBe('2026-10-31')
        ->and($snapshot->currency)->toBe('THB')
        ->and($snapshot->cash)->toBe(500000)
        ->and($snapshot->total_assets)->toBe(500000)
        ->and($snapshot->net_worth)->toBe(500000)
        ->and($snapshot->unconverted_items)->toBe(0);
});

test('taking a snapshot twice on the same day updates it', function () {
    Carbon::setTestNow('2026-10-31 10:00:00');
    $workspace = Workspace::factory()->create();
    $account = Account::factory()->for($workspace)->create(['opening_balance' => 100000]);
    app(NetWorthService::class)->snapshot($workspace);

    $account->update(['opening_balance' => 300000]);
    app(NetWorthService::class)->snapshot($workspace);

    expect(NetWorthSnapshot::count())->toBe(1)->and(NetWorthSnapshot::sole()->net_worth)->toBe(300000);
});

test('snapshots on different days are kept separately', function () {
    $workspace = Workspace::factory()->create();
    $service = app(NetWorthService::class);

    $service->snapshot($workspace, Carbon::parse('2026-09-30'));
    $service->snapshot($workspace, Carbon::parse('2026-10-31'));

    expect(NetWorthSnapshot::count())->toBe(2);
});

test('the command snapshots every workspace', function () {
    Workspace::factory()->count(3)->create();

    $this->artisan('net-worth:snapshot')->assertSuccessful();

    expect(NetWorthSnapshot::count())->toBe(3);
});

test('the command can target a single workspace', function () {
    [$one] = Workspace::factory()->count(2)->create();

    $this->artisan('net-worth:snapshot', ['--workspace' => $one->id])->assertSuccessful();

    expect(NetWorthSnapshot::sole()->workspace_id)->toBe($one->id);
});

test('the command is scheduled for the end of every month', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'net-worth:snapshot'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toContain('55 23');
});

test('the dashboard button saves a snapshot for the current workspace', function () {
    [, $workspace] = actAsMember();
    Account::factory()->for($workspace)->create(['opening_balance' => 250000]);

    Livewire::test(Dashboard::class)->callAction('snapshot')->assertNotified();

    expect($workspace->netWorthSnapshots()->sole()->net_worth)->toBe(250000);
});

test('the trend chart plots the latest snapshots in baht in date order', function () {
    [, $workspace] = actAsMember();
    NetWorthSnapshot::factory()->for($workspace)->create(['date' => '2026-09-30', 'net_worth' => 100000]);
    NetWorthSnapshot::factory()->for($workspace)->create(['date' => '2026-08-31', 'net_worth' => 50000]);

    $widget = Livewire::test(NetWorthTrend::class)->instance();
    $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

    expect($data['labels'])->toBe(['31/08/2026', '30/09/2026'])
        ->and($data['datasets'][0]['data'])->toBe([500.0, 1000.0]);
});

test('the trend chart tells the user when there is no history', function () {
    actAsMember();

    Livewire::test(NetWorthTrend::class)->assertSee('ยังไม่มีข้อมูล');
});

test('the history page only lists the current workspace and allows deleting', function () {
    [, $workspace] = actAsMember();
    $mine = NetWorthSnapshot::factory()->for($workspace)->create();
    $theirs = NetWorthSnapshot::withoutEvents(fn () => NetWorthSnapshot::factory()->for(Workspace::factory()->create())->create());

    $this->get(NetWorthSnapshotResource::getUrl('index'))->assertSuccessful();

    Livewire::test(ListNetWorthSnapshots::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs])
        ->callTableAction('delete', $mine);

    $unscoped = fn (int $id) => NetWorthSnapshot::withoutGlobalScopes()->find($id);

    expect($unscoped($mine->id))->toBeNull()->and($unscoped($theirs->id))->not->toBeNull();
});
