<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Resources\Transactions\Pages\EditTransaction;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Support\Money;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
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

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $modelLabel = 'รายการเงิน';

    protected static ?string $pluralModelLabel = 'รายการเงินเข้า-ออก';

    protected static string|UnitEnum|null $navigationGroup = 'บัญชี';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_id')->label('บัญชี')->relationship('account', 'name')->searchable()->preload()->required(),
                DatePicker::make('date')->label('วันที่')->default(now())->required(),
                Money::input('amount', 'จำนวนเงิน (บวก = เข้า, ลบ = ออก)')->required(),
                Select::make('category_id')->label('หมวดหมู่')->relationship('category', 'name')->searchable()->preload(),
                TextInput::make('description')->label('รายละเอียด')->maxLength(255),
                Select::make('insurance_policy_id')->label('กรมธรรม์ที่เกี่ยวข้อง')->relationship('insurancePolicy', 'name')->searchable()->preload(),
                Textarea::make('notes')->label('บันทึก')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('วันที่')->date()->sortable(),
                TextColumn::make('account.name')->label('บัญชี')->searchable(),
                TextColumn::make('description')->label('รายละเอียด')->searchable()->limit(40),
                TextColumn::make('category.name')->label('หมวดหมู่')->placeholder('-'),
                Money::column('amount', 'จำนวนเงิน')->color(fn (int $state): string => $state < 0 ? 'danger' : 'success')->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('account_id')->label('บัญชี')->relationship('account', 'name'),
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
            'index' => ListTransactions::route('/'),
            'create' => CreateTransaction::route('/create'),
            'edit' => EditTransaction::route('/{record}/edit'),
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
