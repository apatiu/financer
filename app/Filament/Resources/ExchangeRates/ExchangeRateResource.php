<?php

namespace App\Filament\Resources\ExchangeRates;

use App\Filament\Resources\ExchangeRates\Pages\CreateExchangeRate;
use App\Filament\Resources\ExchangeRates\Pages\EditExchangeRate;
use App\Filament\Resources\ExchangeRates\Pages\ListExchangeRates;
use App\Models\ExchangeRate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static ?string $modelLabel = 'อัตราแลกเปลี่ยน';

    protected static ?string $pluralModelLabel = 'อัตราแลกเปลี่ยน';

    protected static string|UnitEnum|null $navigationGroup = 'ตั้งค่า';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('currency')->label('สกุลเงิน')->required()->length(3)
                    ->datalist(['USD', 'EUR', 'JPY', 'GBP', 'CNY', 'SGD', 'HKD', 'AUD'])
                    ->dehydrateStateUsing(fn (string $state): string => strtoupper($state)),
                DatePicker::make('date')->label('วันที่')->default(now())->required(),
                TextInput::make('rate')->label('อัตรา (1 หน่วยของสกุลนี้ = กี่หน่วยสกุลหลัก)')->numeric()->step('0.00000001')->minValue(0)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('currency')->label('สกุลเงิน')->searchable(),
                TextColumn::make('date')->label('วันที่')->date()->sortable(),
                TextColumn::make('rate')->label('อัตรา'),
            ])
            ->defaultSort('date', 'desc')
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

    public static function getPages(): array
    {
        return [
            'index' => ListExchangeRates::route('/'),
            'create' => CreateExchangeRate::route('/create'),
            'edit' => EditExchangeRate::route('/{record}/edit'),
        ];
    }
}
