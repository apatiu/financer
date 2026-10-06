<?php

namespace App\Filament\Resources\NetWorthSnapshots;

use App\Filament\Resources\NetWorthSnapshots\Pages\ListNetWorthSnapshots;
use App\Filament\Support\Money;
use App\Models\NetWorthSnapshot;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NetWorthSnapshotResource extends Resource
{
    protected static ?string $model = NetWorthSnapshot::class;

    protected static ?string $modelLabel = 'ประวัติความมั่งคั่ง';

    protected static ?string $pluralModelLabel = 'ประวัติความมั่งคั่ง';

    protected static string|UnitEnum|null $navigationGroup = 'รายงาน';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('วันที่')->date()->sortable(),
                Money::column('net_worth', 'ความมั่งคั่งสุทธิ')->sortable(),
                Money::column('total_assets', 'สินทรัพย์รวม'),
                Money::column('total_liabilities', 'หนี้สินรวม'),
                TextColumn::make('currency')->label('สกุล'),
                TextColumn::make('unconverted_items')->label('ไม่ได้นับ (ไม่มีอัตราแลกเปลี่ยน)')->placeholder('0'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
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
            'index' => ListNetWorthSnapshots::route('/'),
        ];
    }
}
