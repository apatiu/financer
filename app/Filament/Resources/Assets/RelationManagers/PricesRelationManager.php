<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    protected static ?string $title = 'ประวัติราคา';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')->label('วันที่')->default(now())->required(),
            TextInput::make('price')->label('ราคาต่อหน่วย')->numeric()->step('0.00000001')->required(),
            TextInput::make('source')->label('แหล่งที่มา')->default('manual'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->columns([
                TextColumn::make('date')->label('วันที่')->date()->sortable(),
                TextColumn::make('price')->label('ราคาต่อหน่วย'),
                TextColumn::make('source')->label('แหล่งที่มา'),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
