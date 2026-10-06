<?php

namespace App\Filament\Resources\NetWorthSnapshots\Pages;

use App\Filament\Resources\NetWorthSnapshots\NetWorthSnapshotResource;
use Filament\Resources\Pages\ListRecords;

class ListNetWorthSnapshots extends ListRecords
{
    protected static string $resource = NetWorthSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
