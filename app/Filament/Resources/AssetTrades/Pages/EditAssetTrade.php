<?php

namespace App\Filament\Resources\AssetTrades\Pages;

use App\Filament\Resources\AssetTrades\AssetTradeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetTrade extends EditRecord
{
    protected static string $resource = AssetTradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
