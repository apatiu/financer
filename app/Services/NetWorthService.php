<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\FixedAssetStatus;
use App\Enums\InsuranceStatus;
use App\Models\NetWorthSnapshot;
use App\Models\Workspace;
use Carbon\CarbonInterface;

/**
 * รวมความมั่งคั่งสุทธิของ workspace ณ ปัจจุบัน เป็นสตางค์ในสกุล base_currency
 *
 * - รายการที่ปิด include_in_net_worth (เช่นของคนในครอบครัว) ไม่ถูกนับ
 * - รายการที่ยังไม่มีอัตราแลกเปลี่ยนจะไม่ถูกนับ และแจ้งจำนวนไว้ใน unconverted_items
 * - ทุนประกันไม่นับเป็นทรัพย์สิน นับเฉพาะมูลค่าเวนคืน
 */
class NetWorthService
{
    /**
     * บันทึกความมั่งคั่ง ณ วันที่ระบุ (ค่าเริ่มต้นวันนี้) ถ้าวันนั้นมีอยู่แล้วจะทับด้วยค่าล่าสุด
     */
    public function snapshot(Workspace $workspace, ?CarbonInterface $date = null): NetWorthSnapshot
    {
        return $workspace->netWorthSnapshots()->updateOrCreate(
            ['date' => ($date ?? now())->toDateString()],
            ['currency' => $workspace->base_currency] + $this->summarize($workspace),
        );
    }

    /**
     * @return array{
     *     cash: int, investment_cash: int, investments: int, insurance: int, fixed_assets: int,
     *     total_assets: int, loans: int, credit_card_debt: int, total_liabilities: int,
     *     net_worth: int, unconverted_items: int
     * }
     */
    public function summarize(Workspace $workspace): array
    {
        $converter = new CurrencyConverter($workspace);
        $unconverted = 0;

        $convert = function (int $amount, string $currency) use ($converter, &$unconverted): int {
            $converted = $converter->toBase($amount, $currency);

            if ($converted === null) {
                $unconverted++;

                return 0;
            }

            return $converted;
        };

        $cash = 0;
        $investmentCash = 0;
        $creditCardDebt = 0;

        $accounts = $workspace->accounts()
            ->where('is_archived', false)
            ->where('include_in_net_worth', true)
            ->get();

        foreach ($accounts as $account) {
            $balance = $account->balance();

            match ($account->type) {
                AccountType::Bank, AccountType::Cash => $cash += $convert($balance, $account->currency),
                AccountType::Brokerage, AccountType::Fund, AccountType::Gold => $investmentCash += $convert($balance, $account->currency),
                AccountType::CreditCard => $creditCardDebt += $convert(max(0, -$balance), $account->currency),
            };
        }

        $investments = 0;

        $holdings = $workspace->holdings()
            ->whereIn('account_id', $accounts->modelKeys())
            ->with('asset')
            ->get();

        foreach ($holdings as $holding) {
            $price = $holding->asset->latestPrice();

            if ($price === null) {
                continue;
            }

            $value = bcmul(bcmul((string) $holding->quantity, $price, 8), '100', 8);
            $investments += $convert((int) bcadd($value, '0.5', 0), $holding->asset->currency);
        }

        $insurance = $workspace->insurancePolicies()
            ->where('include_in_net_worth', true)
            ->whereIn('status', [InsuranceStatus::Active, InsuranceStatus::PaidUp])
            ->get()
            ->sum(fn ($policy) => $convert($policy->currentCashValue(), $policy->currency));

        $fixedAssets = $workspace->fixedAssets()
            ->where('status', FixedAssetStatus::Owned)
            ->where('include_in_net_worth', true)
            ->get()
            ->sum(fn ($asset) => $convert($asset->ownedValue(), $asset->currency));

        $loans = $workspace->liabilities()
            ->where('status', 'active')
            ->where('include_in_net_worth', true)
            ->get()
            ->sum(fn ($loan) => $convert($loan->outstanding_balance, $loan->currency));

        $totalAssets = $cash + $investmentCash + $investments + $insurance + $fixedAssets;
        $totalLiabilities = $loans + $creditCardDebt;

        return [
            'cash' => $cash,
            'investment_cash' => $investmentCash,
            'investments' => $investments,
            'insurance' => (int) $insurance,
            'fixed_assets' => (int) $fixedAssets,
            'total_assets' => (int) $totalAssets,
            'loans' => (int) $loans,
            'credit_card_debt' => $creditCardDebt,
            'total_liabilities' => (int) $totalLiabilities,
            'net_worth' => (int) ($totalAssets - $totalLiabilities),
            'unconverted_items' => $unconverted,
        ];
    }
}
