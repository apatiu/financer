<?php

use App\Filament\Resources\InsurancePolicies\Pages\EditInsurancePolicy;
use App\Filament\Resources\InsurancePolicies\RelationManagers\ValuesRelationManager;
use App\Models\InsurancePolicy;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $user = User::factory()->create();
    $this->workspace = $user->workspaces()->sole();
    // ทุนประกัน 1,000,000 บาท
    $this->policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => '2020-03-01', 'sum_assured' => 100000000]);

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($this->workspace);
    Filament::bootCurrentPanel();

    $this->manager = fn (?InsurancePolicy $policy = null) => Livewire::test(ValuesRelationManager::class, [
        'ownerRecord' => $policy ?? $this->policy,
        'pageClass' => EditInsurancePolicy::class,
    ]);
});

test('per thousand converts to a total using the sum assured', function () {
    // 250.50 ต่อ 1,000 × 1,000,000 / 1,000 = 250,500 บาท = 25,050,000 สตางค์
    expect($this->policy->cashValueFromPerThousand('250.50'))->toBe(25050000)
        ->and($this->policy->cashValueFromPerThousand(0))->toBe(0);
});

test('per thousand rounds to the nearest satang', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['sum_assured' => 33333333]);

    // 123.4567 × 333,333.33 / 1,000 = 41,152.2... บาท
    expect($policy->cashValueFromPerThousand('123.4567'))->toBe(4115223);
});

test('a policy without a sum assured cannot convert per thousand values', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['sum_assured' => 0]);

    expect($policy->cashValueFromPerThousand(250))->toBeNull()->and($policy->perThousandFor(100000))->toBeNull();
});

test('a total converts back to per thousand', function () {
    expect($this->policy->perThousandFor(25050000))->toBe('250.50');
});

test('entering per thousand stores the total in satang', function () {
    ($this->manager)()
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'entry_mode' => 'year',
            'policy_year' => 10,
            'value_unit' => 'per_thousand',
            'per_thousand' => 250.5,
        ])
        ->assertHasNoFormErrors();

    $value = $this->policy->values()->sole();

    expect($value->as_of_date->toDateString())->toBe('2030-03-01')
        ->and($value->cash_value)->toBe(25050000);
});

test('entering a total is unchanged', function () {
    ($this->manager)()
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'entry_mode' => 'year',
            'policy_year' => 10,
            'value_unit' => 'total',
            'cash_value' => 250500,
        ])
        ->assertHasNoFormErrors();

    expect($this->policy->values()->sole()->cash_value)->toBe(25050000);
});

test('the per thousand amount is required in that mode', function () {
    ($this->manager)()
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'entry_mode' => 'year', 'policy_year' => 10, 'value_unit' => 'per_thousand',
        ])
        ->assertHasFormErrors(['per_thousand' => 'required']);

    expect($this->policy->values()->count())->toBe(0);
});

test('per thousand is unavailable when the policy has no sum assured', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => '2020-03-01', 'sum_assured' => 0]);

    ($this->manager)($policy)
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'entry_mode' => 'year', 'policy_year' => 1, 'value_unit' => 'per_thousand', 'per_thousand' => 100,
        ])
        ->assertHasFormErrors(['value_unit']);

    expect($policy->values()->count())->toBe(0);
});

test('the bulk action accepts per thousand amounts', function () {
    ($this->manager)()
        ->callAction(TestAction::make('bulkYears')->table(), ['value_unit' => 'per_thousand', 'rows' => [
            ['year' => 1, 'amount' => 100],
            ['year' => 2, 'amount' => 205.5],
        ]])
        ->assertHasNoFormErrors();

    expect($this->policy->values()->orderBy('as_of_date')->pluck('cash_value')->all())->toBe([10000000, 20550000]);
});

test('the bulk action keeps accepting totals', function () {
    ($this->manager)()
        ->callAction(TestAction::make('bulkYears')->table(), ['value_unit' => 'total', 'rows' => [
            ['year' => 1, 'amount' => 100000],
        ]])
        ->assertHasNoFormErrors();

    expect($this->policy->values()->sole()->cash_value)->toBe(10000000);
});

test('the table shows the per thousand equivalent', function () {
    $this->policy->values()->create(['as_of_date' => '2030-03-01', 'cash_value' => 25050000]);

    ($this->manager)()->loadTable()->assertSee('250.50');
});
