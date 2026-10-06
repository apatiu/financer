<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FixedAssets\FixedAssetResource;
use App\Filament\Resources\InsurancePolicies\InsurancePolicyResource;
use App\Services\DueReminderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class DueReminders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('ใกล้ครบกำหนด (30 วันข้างหน้า และที่เลยกำหนดแล้ว)')
            ->records(fn (): array => app(DueReminderService::class)
                ->upcoming(Filament::getTenant())
                ->keyBy('key')
                ->all())
            ->columns([
                TextColumn::make('kind')->label('เรื่อง')->badge(),
                TextColumn::make('title')->label('รายการ'),
                TextColumn::make('due_on')->label('ครบกำหนด')->date(),
                TextColumn::make('days_left')
                    ->label('เหลือเวลา')
                    ->formatStateUsing(fn (int $state): string => match (true) {
                        $state < 0 => 'เลยกำหนด '.abs($state).' วัน',
                        $state === 0 => 'วันนี้',
                        default => "อีก {$state} วัน",
                    })
                    ->color(fn (int $state): string => match (true) {
                        $state < 0 => 'danger',
                        $state <= 7 => 'warning',
                        default => 'gray',
                    })
                    ->badge(),
                TextColumn::make('owner_name')->label('เจ้าของ')->placeholder('ตัวเอง'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('เปิด')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (array $record): string => $record['resource'] === 'insurance'
                        ? InsurancePolicyResource::getUrl('edit', ['record' => $record['record_id']])
                        : FixedAssetResource::getUrl('edit', ['record' => $record['record_id']])),
            ])
            ->emptyStateHeading('ไม่มีรายการใกล้ครบกำหนด')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->paginated(false);
    }
}
