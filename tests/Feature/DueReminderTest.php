<?php

use App\Enums\FixedAssetType;
use App\Enums\InsuranceStatus;
use App\Filament\Widgets\DueReminders;
use App\Models\FixedAsset;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Models\Workspace;
use App\Services\DueReminderService;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-10-06 10:00:00');
    $this->workspace = Workspace::factory()->create();
    $this->reminders = fn (int $days = 30) => app(DueReminderService::class)->upcoming($this->workspace, $days);
});

afterEach(fn () => Carbon::setTestNow());

test('premiums due soon and overdue are listed, far ones are not', function () {
    InsurancePolicy::factory()->for($this->workspace)->create(['name' => 'ใกล้', 'next_premium_due' => '2026-10-20']);
    InsurancePolicy::factory()->for($this->workspace)->create(['name' => 'เลยกำหนด', 'next_premium_due' => '2026-10-01']);
    InsurancePolicy::factory()->for($this->workspace)->create(['name' => 'ไกล', 'next_premium_due' => '2027-03-01']);

    $list = ($this->reminders)();

    expect($list->pluck('title')->all())->toBe(['เลยกำหนด', 'ใกล้'])
        ->and($list[0]['days_left'])->toBe(-5)
        ->and($list[1]['days_left'])->toBe(14);
});

test('due today counts as zero days left', function () {
    InsurancePolicy::factory()->for($this->workspace)->create(['next_premium_due' => '2026-10-06']);

    expect(($this->reminders)()->sole()['days_left'])->toBe(0);
});

test('lapsed and surrendered policies produce no reminders', function () {
    InsurancePolicy::factory()->for($this->workspace)->create(['status' => InsuranceStatus::Lapsed, 'next_premium_due' => '2026-10-10', 'maturity_date' => '2026-10-10']);
    InsurancePolicy::factory()->for($this->workspace)->create(['status' => InsuranceStatus::Surrendered, 'next_premium_due' => '2026-10-10']);

    expect(($this->reminders)())->toBeEmpty();
});

test('paid up policies only remind about maturity', function () {
    InsurancePolicy::factory()->for($this->workspace)->create([
        'status' => InsuranceStatus::PaidUp, 'next_premium_due' => '2026-10-10', 'maturity_date' => '2026-10-15',
    ]);

    expect(($this->reminders)()->pluck('kind')->all())->toBe(['ประกันครบกำหนดสัญญา']);
});

test('vehicle tax and compulsory insurance dates are read from details', function () {
    FixedAsset::factory()->for($this->workspace)->create([
        'type' => FixedAssetType::Vehicle,
        'name' => 'Yaris กข 1234',
        'details' => ['tax_due_on' => '2026-10-12', 'compulsory_insurance_due_on' => '2026-10-30'],
    ]);

    $list = ($this->reminders)();

    expect($list->pluck('kind')->all())->toBe(['ต่อภาษีรถ', 'ต่อ พ.ร.บ.'])
        ->and($list->pluck('days_left')->all())->toBe([6, 24]);
});

test('non vehicles, sold vehicles and bad dates are ignored', function () {
    FixedAsset::factory()->for($this->workspace)->create(['type' => FixedAssetType::Land, 'details' => ['tax_due_on' => '2026-10-12']]);
    FixedAsset::factory()->for($this->workspace)->create(['type' => FixedAssetType::Vehicle, 'status' => 'sold', 'details' => ['tax_due_on' => '2026-10-12']]);
    FixedAsset::factory()->for($this->workspace)->create(['type' => FixedAssetType::Vehicle, 'details' => ['tax_due_on' => 'ไม่ใช่วันที่', 'compulsory_insurance_due_on' => '']]);
    FixedAsset::factory()->for($this->workspace)->create(['type' => FixedAssetType::Vehicle, 'details' => null]);

    expect(($this->reminders)())->toBeEmpty();
});

test('other workspaces and family owned policies are handled', function () {
    InsurancePolicy::factory()->create(['next_premium_due' => '2026-10-10']);
    InsurancePolicy::factory()->for($this->workspace)->create(['next_premium_due' => '2026-10-10', 'owner_name' => 'แม่', 'include_in_net_worth' => false]);

    $list = ($this->reminders)();

    expect($list)->toHaveCount(1)->and($list[0]['owner_name'])->toBe('แม่');
});

test('the window is configurable', function () {
    InsurancePolicy::factory()->for($this->workspace)->create(['next_premium_due' => '2026-10-20']);

    expect(($this->reminders)(7))->toBeEmpty()->and(($this->reminders)(14))->toHaveCount(1);
});

test('the dashboard widget lists reminders for the current workspace', function () {
    $user = User::factory()->create();
    $workspace = $user->workspaces()->sole();
    InsurancePolicy::factory()->for($workspace)->create(['name' => 'สะสมทรัพย์ของพ่อ', 'next_premium_due' => '2026-10-10']);

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($workspace);
    Filament::bootCurrentPanel();

    Livewire::test(DueReminders::class)
        ->loadTable()
        ->assertSee('สะสมทรัพย์ของพ่อ')
        ->assertSee('จ่ายเบี้ยประกัน')
        ->assertSee('อีก 4 วัน');
});

test('the widget shows an empty state when nothing is due', function () {
    $user = User::factory()->create();
    $workspace = $user->workspaces()->sole();

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($workspace);
    Filament::bootCurrentPanel();

    Livewire::test(DueReminders::class)->loadTable()->assertSee('ไม่มีรายการใกล้ครบกำหนด');
});
