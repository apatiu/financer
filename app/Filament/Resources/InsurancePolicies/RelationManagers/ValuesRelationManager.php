<?php

namespace App\Filament\Resources\InsurancePolicies\RelationManagers;

use App\Filament\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $title = 'ตารางมูลค่าเวนคืน';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('as_of_date')->label('ณ วันที่')->required(),
            Money::input('cash_value', 'มูลค่าเวนคืน')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('as_of_date')
            ->columns([
                TextColumn::make('as_of_date')->label('ณ วันที่')->date()->sortable(),
                Money::column('cash_value', 'มูลค่าเวนคืน'),
            ])
            ->defaultSort('as_of_date', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
