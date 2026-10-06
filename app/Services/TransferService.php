<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * โอนเงินระหว่างบัญชี = 2 รายการที่ใช้ transfer_group_id เดียวกัน
 * ขาออก (ลบ) จากบัญชีต้นทาง และขาเข้า (บวก) ที่บัญชีปลายทาง
 * ข้ามสกุลเงินต้องระบุยอดที่ปลายทางได้รับจริง (หน่วยย่อยของสกุลปลายทาง)
 */
class TransferService
{
    /**
     * @return array{out: Transaction, in: Transaction}
     */
    public function transfer(
        Account $from,
        Account $to,
        int $amount,
        CarbonInterface|string $date,
        ?int $receivedAmount = null,
        ?string $description = null,
        ?int $createdBy = null,
    ): array {
        $received = $this->validated($from, $to, $amount, $receivedAmount);

        return DB::transaction(function () use ($from, $to, $amount, $received, $date, $description, $createdBy): array {
            $group = (string) Str::uuid();
            $description ??= "โอนเงิน {$from->name} → {$to->name}";

            $common = [
                'workspace_id' => $from->workspace_id,
                'date' => $date,
                'description' => $description,
                'created_by' => $createdBy,
                'transfer_group_id' => $group,
            ];

            return [
                'out' => Transaction::create($common + ['account_id' => $from->getKey(), 'amount' => -$amount]),
                'in' => Transaction::create($common + ['account_id' => $to->getKey(), 'amount' => $received]),
            ];
        });
    }

    private function validated(Account $from, Account $to, int $amount, ?int $receivedAmount): int
    {
        $errors = [];

        if ($from->is($to)) {
            $errors['to_account_id'] = 'บัญชีปลายทางต้องไม่ใช่บัญชีเดียวกับต้นทาง';
        }

        if ($from->workspace_id !== $to->workspace_id) {
            $errors['to_account_id'] = 'โอนข้ามสมุดบัญชีไม่ได้';
        }

        if ($amount <= 0) {
            $errors['amount'] = 'จำนวนเงินต้องมากกว่า 0';
        }

        $received = $amount;

        if ($from->currency !== $to->currency) {
            if ($receivedAmount === null || $receivedAmount <= 0) {
                $errors['received_amount'] = "ต้องระบุยอดที่ได้รับเป็นสกุล {$to->currency}";
            } else {
                $received = $receivedAmount;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $received;
    }
}
