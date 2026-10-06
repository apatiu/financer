<?php

namespace App\Filament\Resources\FixedAssets\RelationManagers;

use App\Enums\ValuationMethod;
use App\Filament\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ValuationsRelationManager extends RelationManager
{
    protected static string $relationship = 'valuations';

    protected static ?string $title = 'ประวัติการประเมินมูลค่า';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('as_of_date')->label('ณ วันที่')->default(now())->required(),
            Money::input('value', 'มูลค่าทั้งทรัพย์สิน')->required(),
            Select::make('method')->label('วิธีประเมิน')->options(ValuationMethod::class)->default(ValuationMethod::Manual->value)->required(),
            TextInput::make('notes')->label('หมายเหตุ'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('as_of_date')
            ->columns([
                TextColumn::make('as_of_date')->label('ณ วันที่')->date()->sortable(),
                Money::column('value', 'มูลค่า'),
                TextColumn::make('method')->label('วิธีประเมิน')->badge(),
                TextColumn::make('notes')->label('หมายเหตุ'),
            ])
            ->defaultSort('as_of_date', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
