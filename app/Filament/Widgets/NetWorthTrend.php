<?php

namespace App\Filament\Widgets;

use App\Models\NetWorthSnapshot;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class NetWorthTrend extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'แนวโน้มความมั่งคั่งสุทธิ';

    protected ?string $maxHeight = '300px';

    public function getDescription(): ?string
    {
        return $this->snapshots()->isEmpty()
            ? 'ยังไม่มีข้อมูล กด "บันทึกความมั่งคั่งตอนนี้" ด้านบน หรือรอระบบบันทึกอัตโนมัติทุกสิ้นเดือน'
            : '12 ครั้งล่าสุด หน่วย: '.Filament::getTenant()->base_currency;
    }

    protected function getData(): array
    {
        $snapshots = $this->snapshots();
        $series = fn (string $column): array => $snapshots->map(fn (NetWorthSnapshot $s): float => $s->{$column} / 100)->all();

        return [
            'datasets' => [
                ['label' => 'ความมั่งคั่งสุทธิ', 'data' => $series('net_worth'), 'borderColor' => '#f59e0b', 'backgroundColor' => '#f59e0b'],
                ['label' => 'สินทรัพย์รวม', 'data' => $series('total_assets'), 'borderColor' => '#10b981', 'backgroundColor' => '#10b981'],
                ['label' => 'หนี้สินรวม', 'data' => $series('total_liabilities'), 'borderColor' => '#ef4444', 'backgroundColor' => '#ef4444'],
            ],
            'labels' => $snapshots->map(fn (NetWorthSnapshot $s): string => $s->date->format('d/m/Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return Collection<int, NetWorthSnapshot>
     */
    private function snapshots()
    {
        return once(fn () => Filament::getTenant()
            ->netWorthSnapshots()
            ->orderByDesc('date')
            ->limit(12)
            ->get()
            ->reverse()
            ->values());
    }
}
