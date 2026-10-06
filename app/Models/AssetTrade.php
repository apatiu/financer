<?php

namespace App\Models;

use App\Enums\TradeType;
use App\Models\Concerns\BelongsToWorkspace;
use App\Services\HoldingCalculator;
use Database\Factories\AssetTradeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'account_id', 'asset_id', 'transaction_id', 'created_by', 'date', 'type', 'quantity', 'price', 'fee', 'amount', 'notes'])]
class AssetTrade extends Model
{
    /** @use HasFactory<AssetTradeFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => TradeType::class,
            'quantity' => 'decimal:8',
            'price' => 'decimal:8',
            'fee' => 'integer',
            'amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $recalculate = function (self $trade): void {
            app(HoldingCalculator::class)->recalculate($trade->account, $trade->asset);
        };

        static::saved(function (self $trade) use ($recalculate): void {
            if ($trade->wasChanged(['account_id', 'asset_id']) && ! $trade->wasRecentlyCreated) {
                $previous = new self([
                    'account_id' => $trade->getOriginal('account_id'),
                    'asset_id' => $trade->getOriginal('asset_id'),
                ]);
                $recalculate($previous);
            }

            $recalculate($trade);
        });
        static::saved(function (self $trade): void {
            if ($trade->transaction_id !== null && $trade->wasChanged(['amount', 'date'])) {
                Transaction::whereKey($trade->transaction_id)->update([
                    'amount' => $trade->amount,
                    'date' => $trade->date,
                ]);
            }
        });
        static::deleted(function (self $trade) use ($recalculate): void {
            $recalculate($trade);

            if ($trade->transaction_id !== null) {
                $cash = Transaction::withTrashed()->find($trade->transaction_id);
                $trade->isForceDeleting() ? $cash?->forceDelete() : $cash?->delete();
            }
        });
        static::restored(function (self $trade) use ($recalculate): void {
            $recalculate($trade);

            if ($trade->transaction_id !== null) {
                Transaction::withTrashed()->find($trade->transaction_id)?->restore();
            }
        });
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
