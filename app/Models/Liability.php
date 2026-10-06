<?php

namespace App\Models;

use App\Enums\LiabilityType;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\LiabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'fixed_asset_id', 'name', 'type', 'lender', 'principal', 'outstanding_balance', 'interest_rate', 'monthly_payment', 'started_on', 'ends_on', 'status', 'notes', 'currency', 'owner_name', 'include_in_net_worth'])]
class Liability extends Model
{
    /** @use HasFactory<LiabilityFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'include_in_net_worth' => 'boolean',
            'type' => LiabilityType::class,
            'principal' => 'integer',
            'outstanding_balance' => 'integer',
            'monthly_payment' => 'integer',
            'started_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<FixedAsset, $this>
     */
    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }
}
