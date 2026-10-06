<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\TradeType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetTrade;
use App\Models\Holding;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * บันทึกการซื้อ/ขายสินทรัพย์: คำนวณยอดสุทธิและเครื่องหมายให้ และสร้างรายการเงินสดคู่กันได้
 *
 * ซื้อ: quantity บวก, amount ลบ (ราคา × จำนวน + ค่าธรรมเนียม)
 * ขาย: quantity ลบ, amount บวก (ราคา × จำนวน − ค่าธรรมเนียม)
 * ราคาต่อหน่วยเป็นหน่วยหลักของสกุลสินทรัพย์ (บาท) ส่วนเงินทุกช่องเก็บเป็นสตางค์
 */
class TradeService
{
    private const SCALE = 8;

    /**
     * @param  string  $quantity  จำนวนหน่วยเป็นบวกเสมอ (ระบบใส่เครื่องหมายให้)
     * @param  int|null  $accountAmount  ยอดสุทธิเป็นสกุลของบัญชี (สตางค์ เป็นบวก) ใช้เมื่อสกุลสินทรัพย์ต่างจากสกุลบัญชี
     */
    public function record(
        Account $account,
        Asset $asset,
        TradeType $type,
        CarbonInterface|string $date,
        string $quantity,
        string $price,
        int $fee = 0,
        ?int $accountAmount = null,
        bool $recordCash = true,
        ?string $notes = null,
        ?int $createdBy = null,
    ): AssetTrade {
        $isBuy = $type === TradeType::Buy;

        if (! $isBuy && $type !== TradeType::Sell) {
            throw new \InvalidArgumentException('record() รองรับเฉพาะการซื้อและการขาย');
        }

        $this->validate($account, $asset, $isBuy, $quantity, $price, $fee, $accountAmount);

        $amount = $this->netAmount($isBuy, $quantity, $price, $fee, $accountAmount);

        return DB::transaction(function () use ($account, $asset, $type, $isBuy, $date, $quantity, $price, $fee, $amount, $recordCash, $notes, $createdBy): AssetTrade {
            $transaction = $recordCash ? Transaction::create([
                'workspace_id' => $account->workspace_id,
                'account_id' => $account->getKey(),
                'date' => $date,
                'amount' => $amount,
                'description' => ($isBuy ? 'ซื้อ ' : 'ขาย ').$asset->symbol.' '.$this->trimmed($quantity),
                'created_by' => $createdBy,
            ]) : null;

            return AssetTrade::create([
                'workspace_id' => $account->workspace_id,
                'account_id' => $account->getKey(),
                'asset_id' => $asset->getKey(),
                'transaction_id' => $transaction?->getKey(),
                'created_by' => $createdBy,
                'date' => $date,
                'type' => $type,
                'quantity' => $isBuy ? bcadd($quantity, '0', self::SCALE) : bcsub('0', $quantity, self::SCALE),
                'price' => $price,
                'fee' => $fee,
                'amount' => $amount,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * จำนวนหน่วยที่ถืออยู่ในบัญชีนี้ (ตาม holdings ล่าสุด)
     */
    public function heldQuantity(Account $account, Asset $asset): string
    {
        return bcadd((string) Holding::query()
            ->where('account_id', $account->getKey())
            ->where('asset_id', $asset->getKey())
            ->value('quantity'), '0', self::SCALE);
    }

    private function validate(Account $account, Asset $asset, bool $isBuy, string $quantity, string $price, int $fee, ?int $accountAmount): void
    {
        $errors = [];

        if (! in_array($account->type, [AccountType::Brokerage, AccountType::Fund, AccountType::Gold], true)) {
            $errors['account_id'] = 'บัญชีนี้ไม่ใช่บัญชีลงทุน (หุ้น/กองทุน/ทอง)';
        }

        if ($asset->workspace_id !== null && $asset->workspace_id !== $account->workspace_id) {
            $errors['asset_id'] = 'ไม่พบสินทรัพย์นี้ในสมุดบัญชีนี้';
        }

        if (! is_numeric($quantity) || bccomp($quantity, '0', self::SCALE) <= 0) {
            $errors['quantity'] = 'จำนวนต้องมากกว่า 0';
        }

        if (! is_numeric($price) || bccomp($price, '0', self::SCALE) < 0) {
            $errors['price'] = 'ราคาต้องไม่ติดลบ';
        }

        if ($fee < 0) {
            $errors['fee'] = 'ค่าธรรมเนียมต้องไม่ติดลบ';
        }

        if ($asset->currency !== $account->currency && ($accountAmount === null || $accountAmount <= 0)) {
            $errors['account_amount'] = "ต้องระบุยอดสุทธิเป็นสกุล {$account->currency}";
        }

        if (! $isBuy && $errors === [] && bccomp($quantity, $this->heldQuantity($account, $asset), self::SCALE) > 0) {
            $errors['quantity'] = 'ขายเกินจำนวนที่ถือครอง (ถืออยู่ '.$this->trimmed($this->heldQuantity($account, $asset)).')';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function netAmount(bool $isBuy, string $quantity, string $price, int $fee, ?int $accountAmount): int
    {
        if ($accountAmount !== null) {
            return $isBuy ? -$accountAmount : $accountAmount;
        }

        $gross = (int) bcadd(bcmul(bcmul($quantity, $price, self::SCALE), '100', self::SCALE), '0.5', 0);

        return $isBuy ? -($gross + $fee) : $gross - $fee;
    }

    private function trimmed(string $number): string
    {
        return str_contains($number, '.') ? rtrim(rtrim($number, '0'), '.') : $number;
    }
}
