<?php

namespace App\Filament\Resources\InsurancePolicies\RelationManagers;

use App\Filament\Support\Money;
use App\Models\InsurancePolicy;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $title = 'ตารางมูลค่าเวนคืน';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Radio::make('entry_mode')
                ->label('กรอกแบบ')
                ->options(['year' => 'ตามปีกรมธรรม์ที่', 'date' => 'ตามวันที่'])
                ->disableOptionWhen(fn (string $value): bool => $value === 'year' && ! $this->hasStartDate())
                ->descriptions(['year' => $this->hasStartDate() ? null : 'ต้องกรอก "วันเริ่มคุ้มครอง" ในกรมธรรม์ก่อน'])
                ->default($this->hasStartDate() ? 'year' : 'date')
                ->inline()
                ->live()
                ->required(),
            TextInput::make('policy_year')
                ->label('ปีกรมธรรม์ที่')
                ->integer()
                ->minValue(0)
                ->maxValue(120)
                ->visible(fn (Get $get): bool => $get('entry_mode') === 'year')
                ->required(fn (Get $get): bool => $get('entry_mode') === 'year')
                ->live(onBlur: true)
                ->helperText(fn (Get $get): ?string => $this->yearPreview($get('policy_year'))),
            DatePicker::make('as_of_date')
                ->label('ณ วันที่')
                ->visible(fn (Get $get): bool => $get('entry_mode') !== 'year')
                ->required(fn (Get $get): bool => $get('entry_mode') !== 'year'),
            Money::input('cash_value', 'มูลค่าเวนคืน')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('as_of_date')
            ->columns([
                TextColumn::make('policy_year')
                    ->label('ปีกรมธรรม์ที่')
                    ->state(fn ($record): ?int => $this->policy()->policyYearFor($record->as_of_date))
                    ->placeholder('-'),
                TextColumn::make('as_of_date')->label('ณ วันที่')->date()->sortable(),
                Money::column('cash_value', 'มูลค่าเวนคืน'),
            ])
            ->defaultSort('as_of_date', 'desc')
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data): array => $this->resolveDate($data)),
                $this->bulkYearsAction(),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => $this->withEntryMode($data))
                    ->mutateDataUsing(fn (array $data): array => $this->resolveDate($data)),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    private function bulkYearsAction(): Action
    {
        return Action::make('bulkYears')
            ->label('กรอกหลายปีรวดเดียว')
            ->icon(Heroicon::OutlinedTableCells)
            ->disabled(fn (): bool => ! $this->hasStartDate())
            ->tooltip(fn (): ?string => $this->hasStartDate() ? null : 'ต้องกรอก "วันเริ่มคุ้มครอง" ในกรมธรรม์ก่อน')
            ->modalHeading('กรอกมูลค่าเวนคืนหลายปี')
            ->modalDescription('ปีที่ซ้ำกับที่มีอยู่แล้วจะถูกแทนที่ด้วยมูลค่าใหม่')
            ->schema([
                Repeater::make('rows')
                    ->label('ตารางมูลค่าเวนคืนจากเล่มกรมธรรม์')
                    ->table([
                        Repeater\TableColumn::make('ปีกรมธรรม์ที่'),
                        Repeater\TableColumn::make('มูลค่าเวนคืน'),
                    ])
                    ->schema([
                        TextInput::make('year')->label('ปีที่')->integer()->minValue(0)->maxValue(120)->required()->distinct(),
                        Money::input('cash_value', 'มูลค่าเวนคืน')->required(),
                    ])
                    ->defaultItems(5)
                    ->minItems(1)
                    ->addActionLabel('เพิ่มปี'),
            ])
            ->action(function (array $data): void {
                $policy = $this->policy();

                foreach ($data['rows'] as $row) {
                    $policy->values()->updateOrCreate(
                        ['as_of_date' => $policy->dateForPolicyYear((int) $row['year'])->toDateString()],
                        ['cash_value' => $row['cash_value']],
                    );
                }

                Notification::make()->title('บันทึก '.count($data['rows']).' ปีแล้ว')->success()->send();
            });
    }

    private function policy(): InsurancePolicy
    {
        /** @var InsurancePolicy */
        return $this->getOwnerRecord();
    }

    private function hasStartDate(): bool
    {
        return $this->policy()->start_date !== null;
    }

    private function yearPreview(mixed $year): ?string
    {
        if (blank($year) || ! is_numeric($year)) {
            return null;
        }

        $date = $this->policy()->dateForPolicyYear((int) $year);

        return $date === null ? null : 'ตรงกับวันที่ '.$date->format('d/m/Y');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveDate(array $data): array
    {
        if (($data['entry_mode'] ?? 'date') === 'year') {
            $data['as_of_date'] = $this->policy()->dateForPolicyYear((int) $data['policy_year'])->toDateString();
        }

        unset($data['entry_mode'], $data['policy_year']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withEntryMode(array $data): array
    {
        $year = filled($data['as_of_date'] ?? null)
            ? $this->policy()->policyYearFor(Carbon::parse($data['as_of_date']))
            : null;

        return $data + ['entry_mode' => $year === null ? 'date' : 'year', 'policy_year' => $year];
    }
}
