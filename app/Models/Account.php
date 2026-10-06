<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'name', 'type', 'institution', 'currency', 'opening_balance', 'opened_on', 'is_archived', 'owner_name', 'include_in_net_worth'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'include_in_net_worth' => 'boolean',
            'type' => AccountType::class,
            'opening_balance' => 'integer',
            'opened_on' => 'date',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<Holding, $this>
     */
    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }

    /**
     * ยอดเงินสดคงเหลือ (สตางค์) = ยอดยกมา + ผลรวมรายการ
     */
    public function balance(): int
    {
        return $this->opening_balance + (int) $this->transactions()->sum('amount');
    }
}
