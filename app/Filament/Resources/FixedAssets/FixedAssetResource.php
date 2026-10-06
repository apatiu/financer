<?php

namespace App\Filament\Resources\FixedAssets;

use App\Enums\FixedAssetStatus;
use App\Enums\FixedAssetType;
use App\Filament\Resources\FixedAssets\Pages\CreateFixedAsset;
use App\Filament\Resources\FixedAssets\Pages\EditFixedAsset;
use App\Filament\Resources\FixedAssets\Pages\ListFixedAssets;
use App\Filament\Resources\FixedAssets\RelationManagers\ValuationsRelationManager;
use App\Filament\Support\Money;
use App\Models\FixedAsset;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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

class FixedAssetResource extends Resource
{
    protected static ?string $model = FixedAsset::class;

    protected static ?string $modelLabel = 'ทรัพย์สินถาวร';

    protected static ?string $pluralModelLabel = 'ทรัพย์สินถาวร (ที่ดิน/รถ/บ้าน)';

    protected static string|UnitEnum|null $navigationGroup = 'ทรัพย์สิน';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ข้อมูลทั่วไป')->columns(2)->schema([
                    Select::make('type')->label('ประเภท')->options(FixedAssetType::class)->required()->live(),
                    TextInput::make('name')->label('ชื่อ')->required()->maxLength(255),
                    Select::make('status')->label('สถานะ')->options(FixedAssetStatus::class)->default(FixedAssetStatus::Owned->value)->required(),
                    TextInput::make('ownership_percent')->label('สัดส่วนที่ถือครอง')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(100)->required(),
                    Money::currencyInput(),
                    DatePicker::make('acquired_on')->label('วันที่ได้มา'),
                    Money::input('purchase_price', 'ราคาซื้อ')->default(0)->required(),
                    DatePicker::make('sold_on')->label('วันที่ขาย'),
                    Money::input('sold_price', 'ราคาขาย'),
                    ...Money::ownerFields(),
                ]),
                Section::make('รายละเอียดที่ดิน')->columns(2)
                    ->visible(fn (Get $get): bool => static::typeIs($get, FixedAssetType::Land))
                    ->schema([
                        TextInput::make('details.deed_number')->label('เลขที่โฉนด'),
                        TextInput::make('details.location')->label('ที่ตั้ง'),
                        TextInput::make('details.rai')->label('ไร่')->numeric(),
                        TextInput::make('details.ngan')->label('งาน')->numeric(),
                        TextInput::make('details.sq_wah')->label('ตารางวา')->numeric(),
                    ]),
                Section::make('รายละเอียดรถยนต์')->columns(2)
                    ->visible(fn (Get $get): bool => static::typeIs($get, FixedAssetType::Vehicle))
                    ->schema([
                        TextInput::make('details.plate')->label('ทะเบียน'),
                        TextInput::make('details.brand')->label('ยี่ห้อ'),
                        TextInput::make('details.model')->label('รุ่น'),
                        TextInput::make('details.year')->label('ปี')->numeric(),
                        DatePicker::make('details.tax_due_on')->label('ครบกำหนดภาษี'),
                        DatePicker::make('details.compulsory_insurance_due_on')->label('ครบกำหนด พ.ร.บ.'),
                    ]),
                Section::make('รายละเอียดบ้าน/คอนโด')->columns(2)
                    ->visible(fn (Get $get): bool => static::typeIs($get, FixedAssetType::House, FixedAssetType::Condo))
                    ->schema([
                        TextInput::make('details.location')->label('ที่ตั้ง'),
                    ]),
                Textarea::make('notes')->label('บันทึก')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('ชื่อ')->searchable(),
                TextColumn::make('type')->label('ประเภท')->badge(),
                TextColumn::make('status')->label('สถานะ')->badge(),
                TextColumn::make('ownership_percent')->label('ถือครอง')->suffix('%'),
                Money::column('purchase_price', 'ราคาซื้อ'),
                Money::column('current_value', 'มูลค่าปัจจุบัน')->state(fn ($record) => $record->currentValue()),
                TextColumn::make('currency')->label('สกุล'),
                TextColumn::make('owner_name')->label('เจ้าของ')->placeholder('ตัวเอง'),
                IconColumn::make('include_in_net_worth')->label('นับรวม')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->label('ประเภท')->options(FixedAssetType::class),
                SelectFilter::make('status')->label('สถานะ')->options(FixedAssetStatus::class),
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

    /**
     * state ของ Select เป็น string ตอนแสดงผล แต่เป็น enum หลัง hydrate ตอนบันทึก จึงต้องรองรับทั้งสองแบบ
     */
    protected static function typeIs(Get $get, FixedAssetType ...$types): bool
    {
        $state = $get('type');
        $type = $state instanceof FixedAssetType ? $state : FixedAssetType::tryFrom((string) $state);

        return $type !== null && in_array($type, $types, true);
    }

    public static function getRelations(): array
    {
        return [
            ValuationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFixedAssets::route('/'),
            'create' => CreateFixedAsset::route('/create'),
            'edit' => EditFixedAsset::route('/{record}/edit'),
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
