<?php

namespace App\Filament\Resources\AssetTrades\Pages;

use App\Filament\Resources\AssetTrades\AssetTradeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetTrade extends CreateRecord
{
    protected static string $resource = AssetTradeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
