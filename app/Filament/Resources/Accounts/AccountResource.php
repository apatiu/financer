<?php

namespace App\Filament\Resources\Accounts;

use App\Enums\AccountType;
use App\Filament\Resources\Accounts\Pages\CreateAccount;
use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
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
use Filament\Resources\Resource;
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

    public static function table(Table $table): Table
    {
        return $table
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }
}
