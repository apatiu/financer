<?php

namespace App\Services;

use App\Models\Workspace;

/**
 * แปลงจำนวนเงินหน่วยย่อย (สตางค์/เซนต์) เป็นสกุล base_currency ของ workspace
 * ด้วยอัตราล่าสุดที่ไม่เกินวันนี้ คืน null ถ้ายังไม่มีอัตราของสกุลนั้น
 * ถือว่าทุกสกุลมีหน่วยย่อย 2 ตำแหน่ง
 */
class CurrencyConverter
{
    /** @var array<string, string|null> */
    private array $rates = [];

    public function __construct(private readonly Workspace $workspace) {}

    public function toBase(int $amount, string $currency): ?int
    {
        if ($currency === $this->workspace->base_currency) {
            return $amount;
        }

        $rate = $this->rateFor($currency);

        if ($rate === null) {
            return null;
        }

        $value = bcmul((string) $amount, $rate, 8);

        return (int) ($amount < 0 ? bcsub($value, '0.5', 0) : bcadd($value, '0.5', 0));
    }

    private function rateFor(string $currency): ?string
    {
        if (! array_key_exists($currency, $this->rates)) {
            $this->rates[$currency] = $this->workspace->exchangeRates()
                ->where('currency', $currency)
                ->whereDate('date', '<=', now()->toDateString())
                ->orderByDesc('date')
                ->value('rate');
        }

        return $this->rates[$currency];
    }
}
