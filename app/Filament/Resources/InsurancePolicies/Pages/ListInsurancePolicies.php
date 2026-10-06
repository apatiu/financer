<?php

namespace App\Filament\Resources\InsurancePolicies\Pages;

use App\Filament\Resources\InsurancePolicies\InsurancePolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsurancePolicies extends ListRecords
{
    protected static string $resource = InsurancePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
