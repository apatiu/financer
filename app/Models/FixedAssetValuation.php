<?php

namespace App\Models;

use App\Enums\ValuationMethod;
use Database\Factories\FixedAssetValuationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fixed_asset_id', 'as_of_date', 'value', 'method', 'notes'])]
class FixedAssetValuation extends Model
{
    /** @use HasFactory<FixedAssetValuationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'as_of_date' => 'date:Y-m-d',
            'value' => 'integer',
            'method' => ValuationMethod::class,
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
