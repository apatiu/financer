<?php

namespace App\Filament\Resources\Assets;

use App\Enums\AssetType;
use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\RelationManagers\PricesRelationManager;
use App\Filament\Support\Money;
use App\Models\Asset;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $modelLabel = 'สินทรัพย์ลงทุน';

    protected static ?string $pluralModelLabel = 'สินทรัพย์ลงทุน (หุ้น/กองทุน/ทอง)';

    protected static string|UnitEnum|null $navigationGroup = 'ลงทุน';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')->label('ประเภท')->options(AssetType::class)->required(),
                TextInput::make('symbol')->label('สัญลักษณ์ / รหัสกองทุน')->required()->maxLength(255),
                TextInput::make('name')->label('ชื่อ')->required()->maxLength(255),
                TextInput::make('exchange')->label('ตลาด (เช่น SET, NASDAQ)')->default('')->dehydrateStateUsing(fn (?string $state): string => $state ?? ''),
                Money::currencyInput(),
                Select::make('unit')->label('หน่วย')->options([
                    'share' => 'หุ้น', 'unit' => 'หน่วยลงทุน', 'baht_weight' => 'บาททองคำ', 'gram' => 'กรัม', 'oz' => 'ออนซ์',
                ])->default('share')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('symbol')->label('สัญลักษณ์')->searchable(),
                TextColumn::make('name')->label('ชื่อ')->searchable(),
                TextColumn::make('type')->label('ประเภท')->badge(),
                TextColumn::make('exchange')->label('ตลาด'),
                TextColumn::make('currency')->label('สกุล'),
                TextColumn::make('latest_price')->label('ราคาล่าสุด')->state(fn ($record) => $record->latestPrice())->placeholder('-'),
                IconColumn::make('is_shared')->label('ส่วนกลาง')->boolean()->state(fn ($record) => $record->workspace_id === null),
            ])
            ->filters([
                SelectFilter::make('type')->label('ประเภท')->options(AssetType::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PricesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }

    protected static bool $isScopedToTenant = false;

    /**
     * @return Builder<Asset>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where(function (Builder $query): void {
            $query->whereNull('workspace_id')->orWhere('workspace_id', Filament::getTenant()?->getKey());
        });
    }

    public static function canEdit(Model $record): bool
    {
        return $record->workspace_id !== null;
    }

    public static function canDelete(Model $record): bool
    {
        return $record->workspace_id !== null;
    }
}
