<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'account_id', 'category_id', 'insurance_policy_id', 'created_by', 'date', 'amount', 'description', 'notes', 'transfer_group_id'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    /**
     * ลบ/กู้คืนขาหนึ่งของการโอน ให้ขาที่เหลือตามไปด้วยเสมอ ยอดบัญชีจะได้ไม่เพี้ยน
     */
    protected static function booted(): void
    {
        static::deleted(function (self $transaction): void {
            if ($transaction->transfer_group_id === null) {
                return;
            }

            $transaction->isForceDeleting()
                ? $transaction->siblings(withTrashed: true)->each->forceDelete()
                : $transaction->siblings()->each->delete();
        });

        static::restored(function (self $transaction): void {
            if ($transaction->transfer_group_id !== null) {
                $transaction->siblings(withTrashed: true)->filter->trashed()->each->restore();
            }
        });
    }

    public function isTransfer(): bool
    {
        return $this->transfer_group_id !== null;
    }

    /**
     * @return Collection<int, self>
     */
    public function siblings(bool $withTrashed = false): Collection
    {
        $query = $withTrashed ? static::withTrashed() : static::query();

        return $query
            ->where('transfer_group_id', $this->transfer_group_id)
            ->whereKeyNot($this->getKey())
            ->get();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<InsurancePolicy, $this>
     */
    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class);
    }
}
