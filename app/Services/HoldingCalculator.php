<?php

namespace App\Services;

use App\Enums\TradeType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetTrade;
use App\Models\Holding;

/**
 * คำนวณยอดถือครองด้วยต้นทุนถัวเฉลี่ยถ่วงน้ำหนัก จาก asset_trades (source of truth)
 *
 * quantity ใน trade: บวก = หน่วยเพิ่ม, ลบ = หน่วยลด
 * split/bonus: quantity คือจำนวนหน่วยที่ได้เพิ่ม (ต้นทุนรวมไม่เปลี่ยน)
 * เงินทุกช่อง (amount, cost_basis, realized_gain) เป็นสตางค์
 */
class HoldingCalculator
{
    private const SCALE = 8;

    public function recalculate(Account $account, Asset $asset): Holding
    {
        $quantity = '0';
        $cost = 0;
        $realized = 0;

        $trades = $account->hasMany(AssetTrade::class)
            ->where('asset_id', $asset->getKey())
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($trades as $trade) {
            $units = bcadd((string) $trade->quantity, '0', self::SCALE);

            switch ($trade->type) {
                case TradeType::Buy:
                case TradeType::TransferIn:
                    $quantity = bcadd($quantity, $units, self::SCALE);
                    $cost += abs($trade->amount);
                    break;

                case TradeType::Split:
                case TradeType::Bonus:
                    $quantity = bcadd($quantity, $units, self::SCALE);
                    break;

                case TradeType::Sell:
                case TradeType::TransferOut:
                    $sold = $this->clampToHeld(ltrim($units, '-'), $quantity);

                    if (bccomp($sold, '0', self::SCALE) === 0) {
                        break;
                    }

                    $removedCost = $this->costOf($sold, $quantity, $cost);

                    if ($trade->type === TradeType::Sell) {
                        $realized += $trade->amount - $removedCost;
                    }

                    $quantity = bcsub($quantity, $sold, self::SCALE);
                    $cost -= $removedCost;
                    break;

                case TradeType::Dividend:
                    break;
            }
        }

        return Holding::updateOrCreate(
            ['account_id' => $account->getKey(), 'asset_id' => $asset->getKey()],
            [
                'workspace_id' => $account->workspace_id,
                'quantity' => $quantity,
                'cost_basis' => $cost,
                'realized_gain' => $realized,
            ],
        );
    }

    private function clampToHeld(string $sold, string $held): string
    {
        return bccomp($sold, $held, self::SCALE) > 0 ? $held : $sold;
    }

    /**
     * ต้นทุนของหน่วยที่ออกไป ถ้าขายหมดตัดต้นทุนที่เหลือทั้งหมดเพื่อไม่ให้เศษปัดค้าง
     */
    private function costOf(string $sold, string $held, int $cost): int
    {
        if (bccomp($sold, $held, self::SCALE) === 0) {
            return $cost;
        }

        $exact = bcdiv(bcmul((string) $cost, $sold, self::SCALE), $held, self::SCALE);

        return (int) bcadd($exact, '0.5', 0);
    }
}
