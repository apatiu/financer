<?php

namespace App\Filament\Resources\AssetTrades;

use App\Enums\AccountType;
use App\Enums\TradeType;
use App\Filament\Resources\AssetTrades\Pages\CreateAssetTrade;
use App\Filament\Resources\AssetTrades\Pages\EditAssetTrade;
use App\Filament\Resources\AssetTrades\Pages\ListAssetTrades;
use App\Filament\Support\Money;
use App\Models\AssetTrade;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AssetTradeResource extends Resource
{
    protected static ?string $model = AssetTrade::class;

    protected static ?string $modelLabel = 'รายการซื้อขาย';

    protected static ?string $pluralModelLabel = 'รายการซื้อขาย';

    protected static string|UnitEnum|null $navigationGroup = 'ลงทุน';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_id')->label('บัญชี')
                    ->relationship('account', 'name', fn (Builder $query) => $query->whereIn('type', [AccountType::Brokerage, AccountType::Fund, AccountType::Gold]))
                    ->searchable()->preload()->required(),
                Select::make('asset_id')->label('สินทรัพย์')
                    ->relationship('asset', 'symbol', fn (Builder $query) => $query->where(function (Builder $query): void {
                        $query->whereNull('workspace_id')->orWhere('workspace_id', Filament::getTenant()?->getKey());
                    }))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->symbol} - {$record->name}")
                    ->searchable(['symbol', 'name'])->preload()->required(),
                DatePicker::make('date')->label('วันที่')->default(now())->required(),
                Select::make('type')->label('ประเภท')->options(TradeType::class)->required(),
                TextInput::make('quantity')->label('จำนวนหน่วย (ซื้อ/เพิ่ม = บวก, ขาย/ลด = ลบ)')->numeric()->step('0.00000001')->default(0)->required(),
                TextInput::make('price')->label('ราคาต่อหน่วย')->numeric()->step('0.00000001'),
                Money::input('fee', 'ค่าธรรมเนียมรวม')->default(0)->required(),
                Money::input('amount', 'ยอดสุทธิ (จ่ายออก = ลบ, รับเข้า = บวก)')->default(0)->required(),
                Textarea::make('notes')->label('บันทึก')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('วันที่')->date()->sortable(),
                TextColumn::make('account.name')->label('บัญชี')->searchable(),
                TextColumn::make('asset.symbol')->label('สินทรัพย์')->searchable(),
                TextColumn::make('type')->label('ประเภท')->badge(),
                TextColumn::make('quantity')->label('จำนวน'),
                TextColumn::make('price')->label('ราคา'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetTrades::route('/'),
            'create' => CreateAssetTrade::route('/create'),
            'edit' => EditAssetTrade::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
