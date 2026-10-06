<?php

namespace App\Filament\Resources\Liabilities;

use App\Enums\LiabilityType;
use App\Filament\Resources\Liabilities\Pages\CreateLiability;
use App\Filament\Resources\Liabilities\Pages\EditLiability;
use App\Filament\Resources\Liabilities\Pages\ListLiabilities;
use App\Filament\Support\Money;
use App\Models\Liability;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LiabilityResource extends Resource
{
    protected static ?string $model = Liability::class;

    protected static ?string $modelLabel = 'หนี้สิน';

    protected static ?string $pluralModelLabel = 'หนี้สิน';

    protected static string|UnitEnum|null $navigationGroup = 'ทรัพย์สิน';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('ชื่อ')->required()->maxLength(255),
                Select::make('type')->label('ประเภท')->options(LiabilityType::class)->required(),
                TextInput::make('lender')->label('ผู้ให้กู้'),
                Select::make('fixed_asset_id')->label('ทรัพย์สินที่ผูกอยู่ (เช่น รถ/บ้านที่ผ่อน)')->relationship('fixedAsset', 'name')->searchable()->preload(),
                Money::currencyInput(),
                Money::input('principal', 'เงินต้นตอนกู้')->default(0)->required(),
                Money::input('outstanding_balance', 'ยอดคงค้างล่าสุด')->default(0)->required(),
                TextInput::make('interest_rate')->label('ดอกเบี้ย')->numeric()->suffix('% ต่อปี'),
                Money::input('monthly_payment', 'ผ่อนต่อเดือน')->default(0),
                DatePicker::make('started_on')->label('วันที่เริ่ม'),
                DatePicker::make('ends_on')->label('วันที่สิ้นสุด'),
                Select::make('status')->label('สถานะ')->options(['active' => 'ยังผ่อนอยู่', 'closed' => 'ปิดแล้ว'])->default('active')->required(),
                ...Money::ownerFields(),
                Textarea::make('notes')->label('บันทึก')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('ชื่อ')->searchable(),
                TextColumn::make('type')->label('ประเภท')->badge(),
                TextColumn::make('lender')->label('ผู้ให้กู้')->toggleable(),
                Money::column('outstanding_balance', 'ยอดคงค้าง'),
                TextColumn::make('currency')->label('สกุล'),
                Money::column('monthly_payment', 'ผ่อน/เดือน'),
                TextColumn::make('status')->label('สถานะ')->badge(),
                TextColumn::make('owner_name')->label('เจ้าของ')->placeholder('ตัวเอง'),
                IconColumn::make('include_in_net_worth')->label('นับรวม')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->label('ประเภท')->options(LiabilityType::class),
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
            'index' => ListLiabilities::route('/'),
            'create' => CreateLiability::route('/create'),
            'edit' => EditLiability::route('/{record}/edit'),
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
