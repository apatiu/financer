<?php

namespace App\Models;

use App\Enums\FixedAssetStatus;
use App\Enums\FixedAssetType;
use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonInterface;
use Database\Factories\FixedAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'type', 'name', 'owner_name', 'ownership_percent', 'include_in_net_worth', 'acquired_on', 'purchase_price', 'details', 'status', 'sold_on', 'sold_price', 'notes', 'currency'])]
class FixedAsset extends Model
{
    /** @use HasFactory<FixedAssetFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FixedAssetType::class,
            'status' => FixedAssetStatus::class,
            'ownership_percent' => 'decimal:2',
            'include_in_net_worth' => 'boolean',
            'acquired_on' => 'date',
            'sold_on' => 'date',
            'purchase_price' => 'integer',
            'sold_price' => 'integer',
            'details' => 'array',
        ];
    }

    /**
     * @return HasMany<FixedAssetValuation, $this>
     */
    public function valuations(): HasMany
    {
        return $this->hasMany(FixedAssetValuation::class);
    }

    /**
     * @return HasMany<InsurancePolicy, $this>
     */
    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    /**
     * @return HasMany<Liability, $this>
     */
    public function liabilities(): HasMany
    {
        return $this->hasMany(Liability::class);
    }

    /**
     * มูลค่าล่าสุดของทั้งทรัพย์สิน (สตางค์) ไม่เกินวันที่ระบุ ถ้ายังไม่มีการประเมินใช้ราคาซื้อ
     */
    public function currentValue(?CarbonInterface $asOf = null): int
    {
        $valuation = $this->valuations()
            ->whereDate('as_of_date', '<=', ($asOf ?? now())->toDateString())
            ->orderByDesc('as_of_date')
            ->first();

        return $valuation?->value ?? $this->purchase_price;
    }

    /**
     * มูลค่าส่วนที่ตัวเองถือครอง (สตางค์) = มูลค่า × สัดส่วนถือครอง
     */
    public function ownedValue(?CarbonInterface $asOf = null): int
    {
        return (int) round($this->currentValue($asOf) * (float) $this->ownership_percent / 100);
    }
}
