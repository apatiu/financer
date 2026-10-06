<?php

use App\Filament\Resources\InsurancePolicies\Pages\EditInsurancePolicy;
use App\Filament\Resources\InsurancePolicies\RelationManagers\ValuesRelationManager;
use App\Models\InsurancePolicy;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $user = User::factory()->create();
    $this->workspace = $user->workspaces()->sole();
    $this->policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => '2020-03-01']);

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($this->workspace);
    Filament::bootCurrentPanel();

    $this->manager = fn (?InsurancePolicy $policy = null) => Livewire::test(ValuesRelationManager::class, [
        'ownerRecord' => $policy ?? $this->policy,
        'pageClass' => EditInsurancePolicy::class,
    ]);
});

test('a policy year maps to the anniversary of the start date', function () {
    expect($this->policy->dateForPolicyYear(0)->toDateString())->toBe('2020-03-01')
        ->and($this->policy->dateForPolicyYear(5)->toDateString())->toBe('2025-03-01')
        ->and($this->policy->policyYearFor(Carbon::parse('2025-03-01')))->toBe(5)
        ->and($this->policy->policyYearFor(Carbon::parse('2025-03-02')))->toBeNull()
        ->and($this->policy->policyYearFor(Carbon::parse('2019-03-01')))->toBeNull();
});

test('a leap day start date does not overflow', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => '2020-02-29']);

    expect($policy->dateForPolicyYear(1)->toDateString())->toBe('2021-02-28')
        ->and($policy->policyYearFor(Carbon::parse('2021-02-28')))->toBe(1)
        ->and($policy->dateForPolicyYear(4)->toDateString())->toBe('2024-02-29');
});

test('a policy without a start date has no year mapping', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => null]);

    expect($policy->dateForPolicyYear(3))->toBeNull()
        ->and($policy->policyYearFor(Carbon::parse('2025-03-01')))->toBeNull();
});

test('entering a policy year stores the matching date and satang value', function () {
    ($this->manager)()
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'entry_mode' => 'year',
            'value_unit' => 'total',
            'policy_year' => 5,
            'cash_value' => 180000.50,
        ])
        ->assertHasNoFormErrors();

    $value = $this->policy->values()->sole();

    expect($value->as_of_date->toDateString())->toBe('2025-03-01')
        ->and($value->cash_value)->toBe(18000050);
});

test('entering a date still works', function () {
    ($this->manager)()
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'entry_mode' => 'date',
            'value_unit' => 'total',
            'as_of_date' => '2024-07-15',
            'cash_value' => 1000,
        ])
        ->assertHasNoFormErrors();

    expect($this->policy->values()->sole()->as_of_date->toDateString())->toBe('2024-07-15');
});

test('the year is required in year mode', function () {
    ($this->manager)()
        ->callAction(TestAction::make(CreateAction::class)->table(), ['entry_mode' => 'year', 'value_unit' => 'total', 'cash_value' => 1000])
        ->assertHasFormErrors(['policy_year' => 'required']);

    expect($this->policy->values()->count())->toBe(0);
});

test('year mode is unavailable without a start date', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => null]);

    ($this->manager)($policy)
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'entry_mode' => 'year', 'value_unit' => 'total', 'policy_year' => 2, 'cash_value' => 1000,
        ])
        ->assertHasFormErrors(['entry_mode']);

    expect($policy->values()->count())->toBe(0);
});

test('editing an anniversary row opens in year mode and can change the year', function () {
    $value = $this->policy->values()->create(['as_of_date' => '2025-03-01', 'cash_value' => 100000]);

    ($this->manager)()
        ->loadTable()
        ->mountAction(TestAction::make(EditAction::class)->table($value))
        ->assertSchemaStateSet(['entry_mode' => 'year', 'policy_year' => 5, 'cash_value' => 1000, 'value_unit' => 'total'])
        ->setActionData(['entry_mode' => 'year', 'value_unit' => 'total', 'policy_year' => 6, 'cash_value' => 1200])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    expect($value->fresh()->as_of_date->toDateString())->toBe('2026-03-01')
        ->and($value->fresh()->cash_value)->toBe(120000);
});

test('editing a non anniversary row opens in date mode', function () {
    $value = $this->policy->values()->create(['as_of_date' => '2024-07-15', 'cash_value' => 100000]);

    ($this->manager)()
        ->loadTable()
        ->mountAction(TestAction::make(EditAction::class)->table($value))
        ->assertSchemaStateSet(['entry_mode' => 'date']);
});

test('the table shows the policy year of anniversary rows', function () {
    $this->policy->values()->create(['as_of_date' => '2025-03-01', 'cash_value' => 100000]);
    $this->policy->values()->create(['as_of_date' => '2024-07-15', 'cash_value' => 90000]);

    ($this->manager)()->loadTable()->assertSee('5')->assertCountTableRecords(2);
});

test('the bulk action creates one row per year and replaces existing ones', function () {
    $this->policy->values()->create(['as_of_date' => '2021-03-01', 'cash_value' => 1]);

    ($this->manager)()
        ->callAction(TestAction::make('bulkYears')->table(), ['value_unit' => 'total', 'rows' => [
            ['year' => 1, 'amount' => 5000],
            ['year' => 2, 'amount' => 12000.25],
            ['year' => 3, 'amount' => 20000],
        ]])
        ->assertHasNoFormErrors()
        ->assertNotified();

    $values = $this->policy->values()->orderBy('as_of_date')->get();

    expect($values)->toHaveCount(3)
        ->and($values->pluck('as_of_date')->map->toDateString()->all())->toBe(['2021-03-01', '2022-03-01', '2023-03-01'])
        ->and($values->pluck('cash_value')->all())->toBe([500000, 1200025, 2000000]);
});

test('the bulk action rejects duplicate years', function () {
    ($this->manager)()
        ->callAction(TestAction::make('bulkYears')->table(), ['value_unit' => 'total', 'rows' => [
            ['year' => 1, 'amount' => 5000],
            ['year' => 1, 'amount' => 6000],
        ]])
        ->assertHasFormErrors();

    expect($this->policy->values()->count())->toBe(0);
});

test('the bulk action is disabled without a start date', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => null]);

    ($this->manager)($policy)->assertActionDisabled(TestAction::make('bulkYears')->table());
});
