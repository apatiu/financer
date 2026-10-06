<?php

namespace App\Filament\Resources\AssetTrades\Tables;

use App\Enums\TradeType;
use App\Filament\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AssetTradesTable
{
    public static function configure(Table $table)
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('วันที่')->date()->sortable(),
                TextColumn::make('account.name')->label('บัญชี')->searchable(),
                TextColumn::make('asset.symbol')->label('สินทรัพย์')->searchable(),
                TextColumn::make('type')->label('ประเภท')->badge(),
                TextColumn::make('quantity')
                    ->label('จำนวน')
                    ->numeric(),
                TextColumn::make('price')
                    ->label('ราคา')
                    ->numeric(2),
                Money::column('amount', 'ยอดสุทธิ'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('type')->label('ประเภท')->options(TradeType::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
