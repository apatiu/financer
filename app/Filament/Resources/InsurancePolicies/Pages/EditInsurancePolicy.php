<?php

namespace App\Filament\Resources\InsurancePolicies\Pages;

use App\Filament\Resources\InsurancePolicies\InsurancePolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditInsurancePolicy extends EditRecord
{
    protected static string $resource = InsurancePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
