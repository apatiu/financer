<?php

namespace App\Filament\Resources\Accounts\RelationManagers;

use App\Enums\AccountType;
use App\Filament\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class HoldingsRelationManager extends RelationManager
{
    protected static string $relationship = 'holdings';

    protected static ?string $title = 'ยอดถือครอง';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return in_array($ownerRecord->type, [AccountType::Brokerage, AccountType::Fund, AccountType::Gold], true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->where('quantity', '>', 0))
            ->columns([
                TextColumn::make('asset.symbol')->label('สินทรัพย์')->searchable(),
                TextColumn::make('asset.name')->label('ชื่อ'),
                TextColumn::make('quantity')->label('จำนวน')->numeric(),
                Money::column('cost_basis', 'ต้นทุนรวม'),
                TextColumn::make('latest_price')
                    ->label('ราคาล่าสุด')
                    ->state(fn ($record) => $record->asset->latestPrice())
                    ->placeholder('-'),
                Money::column('realized_gain', 'กำไร/ขาดทุนที่รับรู้แล้ว')
                    ->color(fn (int $state): string => $state < 0 ? 'danger' : 'success'),
            ])
            ->emptyStateHeading('ยังไม่มียอดถือครอง');
    }
}
