<?php

namespace App\Filament\Widgets;

use App\Services\NetWorthService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NetWorthOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'ความมั่งคั่งสุทธิ';

    protected function getDescription(): ?string
    {
        $unconverted = $this->summary()['unconverted_items'];

        return $unconverted > 0
            ? "มี {$unconverted} รายการที่ยังไม่มีอัตราแลกเปลี่ยน จึงไม่ถูกนับ (เพิ่มได้ที่ ตั้งค่า > อัตราแลกเปลี่ยน)"
            : null;
    }

    protected function getStats(): array
    {
        $summary = $this->summary();
        $currency = Filament::getTenant()->base_currency;

        $format = fn (int $amount): string => number_format($amount / 100, 0).' '.$currency;

        return [
            Stat::make('ความมั่งคั่งสุทธิ', $format($summary['net_worth']))
                ->color($summary['net_worth'] < 0 ? 'danger' : 'success'),
            Stat::make('สินทรัพย์รวม', $format($summary['total_assets'])),
            Stat::make('หนี้สินรวม', $format($summary['total_liabilities']))
                ->description('สินเชื่อ '.$format($summary['loans']).' · บัตรเครดิต '.$format($summary['credit_card_debt'])),
            Stat::make('เงินสดและเงินฝาก', $format($summary['cash'])),
            Stat::make('การลงทุน', $format($summary['investments'] + $summary['investment_cash']))
                ->description('หุ้น กองทุน ทองคำ รวมเงินสดในบัญชีลงทุน'),
            Stat::make('ประกัน (มูลค่าเวนคืน)', $format($summary['insurance'])),
            Stat::make('ที่ดิน รถ และทรัพย์สินถาวร', $format($summary['fixed_assets'])),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function summary(): array
    {
        return once(fn () => app(NetWorthService::class)->summarize(Filament::getTenant()));
    }
}
