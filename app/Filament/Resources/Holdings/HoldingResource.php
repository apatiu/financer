<?php

namespace App\Filament\Resources\Holdings;

use App\Filament\Resources\Holdings\Pages\ListHoldings;
use App\Filament\Support\Money;
use App\Models\Holding;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class HoldingResource extends Resource
{
    protected static ?string $model = Holding::class;

    protected static ?string $modelLabel = 'ยอดถือครอง';

    protected static ?string $pluralModelLabel = 'ยอดถือครอง';

    protected static string|UnitEnum|null $navigationGroup = 'ลงทุน';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.name')->label('บัญชี')->searchable(),
                TextColumn::make('asset.symbol')->label('สินทรัพย์')->searchable(),
                TextColumn::make('quantity')->label('จำนวน'),
                Money::column('cost_basis', 'ต้นทุนรวม'),
                TextColumn::make('latest_price')->label('ราคาล่าสุด')->state(fn ($record) => $record->asset->latestPrice())->placeholder('-'),
                Money::column('realized_gain', 'กำไร/ขาดทุนที่รับรู้แล้ว')->color(fn (int $state): string => $state < 0 ? 'danger' : 'success'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHoldings::route('/'),
        ];
    }
}
