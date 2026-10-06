<?php

namespace App\Filament\Resources\Accounts;

use App\Enums\AccountType;
use App\Filament\Resources\Accounts\Pages\CreateAccount;
use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Accounts\Pages\ViewAccount;
use App\Filament\Resources\Accounts\RelationManagers\AssetTradesRelationManager;
use App\Filament\Resources\Accounts\RelationManagers\HoldingsRelationManager;
use App\Filament\Resources\Accounts\RelationManagers\TransactionsRelationManager;
use App\Filament\Support\Money;
use App\Models\Account;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'บัญชี';

    protected static ?string $pluralModelLabel = 'บัญชี (เงินสด/ธนาคาร/หุ้น/กองทุน/ทอง)';

    protected static string|UnitEnum|null $navigationGroup = 'บัญชี';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('ชื่อบัญชี')->required()->maxLength(255),
                Select::make('type')->label('ประเภท')->options(AccountType::class)->required(),
                TextInput::make('institution')->label('สถาบัน (ธนาคาร/โบรกเกอร์/ร้านทอง)')->maxLength(255),
                Money::currencyInput(),
                Money::input('opening_balance', 'ยอดยกมา')->default(0)->required(),
                DatePicker::make('opened_on')->label('วันที่เปิดบัญชี'),
                Toggle::make('is_archived')->label('ปิดบัญชีแล้ว'),
                ...Money::ownerFields(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('ข้อมูลบัญชี')->columns(4)->schema([
                TextEntry::make('type')->label('ประเภท')->badge(),
                TextEntry::make('institution')->label('สถาบัน')->placeholder('-'),
                TextEntry::make('currency')->label('สกุลเงิน'),
                TextEntry::make('balance')
                    ->label('ยอดเงินสดคงเหลือ')
                    ->state(fn ($record): int => $record->balance())
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2)),
                TextEntry::make('owner_name')->label('เจ้าของ')->placeholder('ตัวเอง'),
                IconEntry::make('include_in_net_worth')->label('นับรวมในความมั่งคั่งสุทธิ')->boolean(),
                TextEntry::make('opened_on')->label('วันที่เปิดบัญชี')->date()->placeholder('-'),
                IconEntry::make('is_archived')->label('ปิดบัญชีแล้ว')->boolean(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Account $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->label('ชื่อบัญชี')->searchable(),
                TextColumn::make('type')->label('ประเภท')->badge(),
                TextColumn::make('institution')->label('สถาบัน')->toggleable(),
                TextColumn::make('currency')->label('สกุล'),
                Money::column('balance', 'ยอดเงินสดคงเหลือ')->state(fn ($record) => $record->balance()),
                TextColumn::make('owner_name')->label('เจ้าของ')->placeholder('ตัวเอง'),
                IconColumn::make('include_in_net_worth')->label('นับรวม')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->label('ประเภท')->options(AccountType::class),
                TernaryFilter::make('is_archived')->label('ปิดบัญชีแล้ว'),
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
            AssetTradesRelationManager::class,
            HoldingsRelationManager::class,
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'view' => ViewAccount::route('/{record}'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }
}
