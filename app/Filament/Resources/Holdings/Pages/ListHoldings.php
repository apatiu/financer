<?php

namespace App\Filament\Resources\Holdings\Pages;

use App\Filament\Resources\Holdings\HoldingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHoldings extends ListRecords
{
    protected static string $resource = HoldingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
