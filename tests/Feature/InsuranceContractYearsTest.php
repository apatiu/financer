<?php

use App\Enums\InsuranceType;
use App\Filament\Resources\InsurancePolicies\Pages\CreateInsurancePolicy;
use App\Filament\Resources\InsurancePolicies\Pages\EditInsurancePolicy;
use App\Models\InsurancePolicy;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $user = User::factory()->create();
    $this->workspace = $user->workspaces()->sole();

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($this->workspace);
    Filament::bootCurrentPanel();
});

test('entering contract years fills the maturity date from the start date', function () {
    Livewire::test(CreateInsurancePolicy::class)
        ->set('data.start_date', '2020-03-01')
        ->set('data.contract_years', 20)
        ->assertSchemaStateSet(['maturity_date' => '2040-03-01']);
});

test('changing the start date recalculates the maturity date when years are set', function () {
    Livewire::test(CreateInsurancePolicy::class)
        ->set('data.start_date', '2020-03-01')
        ->set('data.contract_years', 10)
        ->set('data.start_date', '2021-06-15')
        ->assertSchemaStateSet(['maturity_date' => '2031-06-15']);
});

test('contract years without a start date leave the maturity date empty', function () {
    Livewire::test(CreateInsurancePolicy::class)
        ->set('data.contract_years', 20)
        ->assertSchemaStateSet(['maturity_date' => null]);
});

test('picking a maturity date shows the matching number of years', function () {
    Livewire::test(CreateInsurancePolicy::class)
        ->set('data.start_date', '2020-03-01')
        ->set('data.maturity_date', '2045-03-01')
        ->assertSchemaStateSet(['contract_years' => 25]);
});

test('a maturity date that is not a whole number of years clears the years', function () {
    Livewire::test(CreateInsurancePolicy::class)
        ->set('data.start_date', '2020-03-01')
        ->set('data.contract_years', 10)
        ->set('data.maturity_date', '2030-09-01')
        ->assertSchemaStateSet(['contract_years' => null, 'maturity_date' => '2030-09-01']);
});

test('the policy saves the calculated maturity date', function () {
    Livewire::test(CreateInsurancePolicy::class)
        ->fillForm([
            'name' => 'สะสมทรัพย์ 20/10',
            'insurer' => 'บริษัทตัวอย่าง',
            'type' => InsuranceType::Endowment->value,
            'sum_assured' => 1000000,
            'premium_amount' => 50000,
            'premium_frequency' => 'yearly',
            'status' => 'active',
        ])
        ->set('data.start_date', '2020-03-01')
        ->set('data.contract_years', 20)
        ->call('create')
        ->assertHasNoFormErrors();

    $policy = InsurancePolicy::sole();

    expect($policy->start_date->toDateString())->toBe('2020-03-01')
        ->and($policy->maturity_date->toDateString())->toBe('2040-03-01');
});

test('editing a policy shows the contract years of its maturity date', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => '2020-03-01', 'maturity_date' => '2040-03-01']);

    Livewire::test(EditInsurancePolicy::class, ['record' => $policy->getKey()])
        ->assertSchemaStateSet(['contract_years' => 20, 'maturity_date' => '2040-03-01']);
});

test('editing a policy with an uneven maturity date leaves the years empty', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => '2020-03-01', 'maturity_date' => '2040-09-09']);

    Livewire::test(EditInsurancePolicy::class, ['record' => $policy->getKey()])
        ->assertSchemaStateSet(['contract_years' => null]);
});

test('editing the years of an existing policy updates the maturity date on save', function () {
    $policy = InsurancePolicy::factory()->for($this->workspace)->create(['start_date' => '2020-03-01', 'maturity_date' => '2040-03-01']);

    Livewire::test(EditInsurancePolicy::class, ['record' => $policy->getKey()])
        ->set('data.contract_years', 25)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($policy->fresh()->maturity_date->toDateString())->toBe('2045-03-01');
});

test('a leap day start date does not overflow when calculating the maturity', function () {
    expect(InsurancePolicy::dateAfterYears('2020-02-29', 1)->toDateString())->toBe('2021-02-28')
        ->and(InsurancePolicy::dateAfterYears('2020-02-29', 4)->toDateString())->toBe('2024-02-29');
});
