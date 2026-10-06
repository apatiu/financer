<?php

namespace App\Filament\Resources\Accounts\RelationManagers;

use App\Enums\AccountType;
use App\Enums\TradeType;
use App\Filament\Actions\RecordTradeAction;
use App\Filament\Resources\AssetTrades\AssetTradeResource;
use App\Filament\Support\Money;
use App\Models\Account;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AssetTradesRelationManager extends RelationManager
{
    protected static string $relationship = 'assetTrades';

    protected static ?string $title = 'รายการซื้อขาย';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return in_array($ownerRecord->type, [AccountType::Brokerage, AccountType::Fund, AccountType::Gold], true);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        /** @var Account $account */
        $account = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('date')->label('วันที่')->date()->sortable(),
                TextColumn::make('asset.symbol')->label('สินทรัพย์')->searchable(),
                TextColumn::make('type')->label('ประเภท')->badge(),
                TextColumn::make('quantity')->label('จำนวน')->numeric(),
                TextColumn::make('price')->label('ราคา')->numeric(2),
                Money::column('fee', 'ค่าธรรมเนียม')->toggleable(isToggledHiddenByDefault: true),
                Money::column('amount', 'ยอดสุทธิ'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('type')->label('ประเภท')->options(TradeType::class),
            ])
            ->headerActions([
                RecordTradeAction::buy($account),
                RecordTradeAction::sell($account),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('แก้ไข')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn ($record): string => AssetTradeResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('ยังไม่มีรายการซื้อขาย')
            ->emptyStateDescription('กด "ซื้อ" เพื่อบันทึกรายการแรก');
    }
}
