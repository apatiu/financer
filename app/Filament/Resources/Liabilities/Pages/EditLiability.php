<?php

namespace App\Filament\Resources\Liabilities\Pages;

use App\Filament\Resources\Liabilities\LiabilityResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditLiability extends EditRecord
{
    protected static string $resource = LiabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
