<?php

namespace App\Filament\Resources\InsurancePolicies;

use App\Enums\InsuranceStatus;
use App\Enums\InsuranceType;
use App\Enums\PremiumFrequency;
use App\Filament\Resources\InsurancePolicies\Pages\CreateInsurancePolicy;
use App\Filament\Resources\InsurancePolicies\Pages\EditInsurancePolicy;
use App\Filament\Resources\InsurancePolicies\Pages\ListInsurancePolicies;
use App\Filament\Resources\InsurancePolicies\RelationManagers\ValuesRelationManager;
use App\Filament\Support\Money;
use App\Models\InsurancePolicy;
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

class InsurancePolicyResource extends Resource
{
    protected static ?string $model = InsurancePolicy::class;

    protected static ?string $modelLabel = 'กรมธรรม์';

    protected static ?string $pluralModelLabel = 'กรมธรรม์ประกัน';

    protected static string|UnitEnum|null $navigationGroup = 'ทรัพย์สิน';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ข้อมูลกรมธรรม์')->columns(2)->schema([
                    TextInput::make('name')->label('ชื่อเรียก')->required()->maxLength(255),
                    TextInput::make('insurer')->label('บริษัทประกัน')->required()->maxLength(255),
                    TextInput::make('policy_number')->label('เลขกรมธรรม์')->maxLength(255),
                    Select::make('type')->label('ประเภท')->options(InsuranceType::class)->required(),
                    TextInput::make('insured_name')->label('ผู้เอาประกัน'),
                    TextInput::make('beneficiary')->label('ผู้รับประโยชน์'),
                    Select::make('fixed_asset_id')->label('ทรัพย์สินที่คุ้มครอง (ประกันรถ/ทรัพย์สิน)')->relationship('fixedAsset', 'name')->searchable()->preload(),
                    Select::make('status')->label('สถานะ')->options(InsuranceStatus::class)->default(InsuranceStatus::Active->value)->required(),
                    ...Money::ownerFields(),
                ]),
                Section::make('เงินและงวดชำระ')->columns(2)->schema([
                    Money::currencyInput(),
                    Money::input('sum_assured', 'ทุนประกัน (ความคุ้มครอง ไม่นับเป็นทรัพย์สิน)')->default(0)->required(),
                    Money::input('premium_amount', 'เบี้ยต่องวด')->default(0)->required(),
                    Select::make('premium_frequency')->label('งวดชำระ')->options(PremiumFrequency::class)->default(PremiumFrequency::Yearly->value)->required(),
                    DatePicker::make('start_date')->label('วันเริ่มคุ้มครอง'),
                    DatePicker::make('premium_end_date')->label('วันสิ้นสุดชำระเบี้ย'),
                    DatePicker::make('maturity_date')->label('วันครบกำหนดสัญญา'),
                    DatePicker::make('next_premium_due')->label('ครบกำหนดจ่ายเบี้ยงวดถัดไป'),
                ]),
                Textarea::make('notes')->label('บันทึก')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('ชื่อเรียก')->searchable(),
                TextColumn::make('insurer')->label('บริษัท')->searchable(),
                TextColumn::make('type')->label('ประเภท')->badge(),
                TextColumn::make('status')->label('สถานะ')->badge(),
                Money::column('sum_assured', 'ทุนประกัน'),
                Money::column('cash_value', 'มูลค่าเวนคืนล่าสุด')->state(fn ($record) => $record->currentCashValue()),
                Money::column('premium_amount', 'เบี้ย/งวด'),
                TextColumn::make('next_premium_due')->label('จ่ายเบี้ยถัดไป')->date()->sortable(),
                TextColumn::make('owner_name')->label('เจ้าของ')->placeholder('ตัวเอง'),
                IconColumn::make('include_in_net_worth')->label('นับรวม')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->label('ประเภท')->options(InsuranceType::class),
                SelectFilter::make('status')->label('สถานะ')->options(InsuranceStatus::class),
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
            ValuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInsurancePolicies::route('/'),
            'create' => CreateInsurancePolicy::route('/create'),
            'edit' => EditInsurancePolicy::route('/{record}/edit'),
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
