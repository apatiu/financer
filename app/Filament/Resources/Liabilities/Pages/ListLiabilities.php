<?php

namespace App\Filament\Resources\Liabilities\Pages;

use App\Filament\Resources\Liabilities\LiabilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLiabilities extends ListRecords
{
    protected static string $resource = LiabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
