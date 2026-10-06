<?php

namespace App\Filament\Resources\Accounts\RelationManagers;

use App\Filament\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'รายการเงินเข้า-ออก';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')->label('วันที่')->default(now())->required(),
            Money::input('amount', 'จำนวนเงิน (บวก = เข้า, ลบ = ออก)')->required(),
            Select::make('category_id')->label('หมวดหมู่')->relationship('category', 'name')->searchable()->preload(),
            TextInput::make('description')->label('รายละเอียด')->maxLength(255),
            Textarea::make('notes')->label('บันทึก'),
        ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('date')->label('วันที่')->date()->sortable(),
                TextColumn::make('description')->label('รายละเอียด')->searchable()->limit(40),
                TextColumn::make('category.name')->label('หมวดหมู่')->placeholder('-'),
                Money::column('amount', 'จำนวนเงิน')->color(fn (int $state): string => $state < 0 ? 'danger' : 'success')->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([
                CreateAction::make()->mutateDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();

                    return $data;
                }),
            ])
            ->recordActions([
                EditAction::make()->hidden(fn ($record): bool => $record->isTransfer()),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->emptyStateHeading('ยังไม่มีรายการเงินเข้า-ออก');
    }
}
