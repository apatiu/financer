<?php

namespace App\Models;

use App\Enums\AssetType;
use Carbon\CarbonInterface;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'type', 'symbol', 'name', 'exchange', 'currency', 'unit'])]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $asset): void {
            $asset->scope_key = $asset->workspace_id ?? 0;
            $asset->exchange ??= '';
        });
    }

    /**
     * @return HasMany<AssetPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(AssetPrice::class);
    }

    /**
     * @return HasMany<AssetTrade, $this>
     */
    public function trades(): HasMany
    {
        return $this->hasMany(AssetTrade::class);
    }

    /**
     * ราคาต่อหน่วยล่าสุดที่ไม่เกินวันที่ระบุ (null ถ้ายังไม่มีราคา)
     */
    public function latestPrice(?CarbonInterface $asOf = null): ?string
    {
        return $this->prices()
            ->whereDate('date', '<=', ($asOf ?? now())->toDateString())
            ->orderByDesc('date')
            ->value('price');
    }
}
