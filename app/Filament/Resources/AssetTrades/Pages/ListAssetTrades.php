<?php

namespace App\Filament\Resources\AssetTrades\Pages;

use App\Filament\Actions\RecordTradeAction;
use App\Filament\Resources\AssetTrades\AssetTradeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetTrades extends ListRecords
{
    protected static string $resource = AssetTradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RecordTradeAction::buy(),
            RecordTradeAction::sell(),
            CreateAction::make(),
        ];
    }
}
