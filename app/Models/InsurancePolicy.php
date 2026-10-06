<?php

namespace App\Models;

use App\Enums\InsuranceStatus;
use App\Enums\InsuranceType;
use App\Enums\PremiumFrequency;
use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\InsurancePolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'fixed_asset_id', 'name', 'insurer', 'policy_number', 'type', 'insured_name', 'beneficiary', 'currency', 'sum_assured', 'premium_amount', 'premium_frequency', 'start_date', 'premium_end_date', 'maturity_date', 'next_premium_due', 'status', 'notes', 'owner_name', 'include_in_net_worth'])]
class InsurancePolicy extends Model
{
    /** @use HasFactory<InsurancePolicyFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'include_in_net_worth' => 'boolean',
            'type' => InsuranceType::class,
            'premium_frequency' => PremiumFrequency::class,
            'status' => InsuranceStatus::class,
            'sum_assured' => 'integer',
            'premium_amount' => 'integer',
            'start_date' => 'date',
            'premium_end_date' => 'date',
            'maturity_date' => 'date',
            'next_premium_due' => 'date',
        ];
    }

    /**
     * @return HasMany<InsurancePolicyValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(InsurancePolicyValue::class);
    }

    /**
     * @return BelongsTo<FixedAsset, $this>
     */
    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * วันครบรอบปีกรมธรรม์ที่ N = วันเริ่มคุ้มครอง + N ปี (ปีที่ 0 คือวันเริ่มคุ้มครอง)
     * คืน null ถ้ากรมธรรม์ยังไม่มีวันเริ่มคุ้มครอง
     */
    public function dateForPolicyYear(int $year): ?CarbonImmutable
    {
        if ($this->start_date === null) {
            return null;
        }

        return CarbonImmutable::parse($this->start_date)->startOfDay()->addYearsNoOverflow($year);
    }

    /**
     * เลขปีกรมธรรม์ของวันที่ที่ตรงกับวันครบรอบพอดี (null ถ้าไม่ตรงหรือไม่มีวันเริ่มคุ้มครอง)
     */
    public function policyYearFor(CarbonInterface $date): ?int
    {
        if ($this->start_date === null) {
            return null;
        }

        $approximate = (int) round(CarbonImmutable::parse($this->start_date)->startOfDay()->diffInYears($date->startOfDay(), false));

        foreach ([$approximate, $approximate - 1, $approximate + 1] as $year) {
            if ($year >= 0 && $this->dateForPolicyYear($year)->isSameDay($date)) {
                return $year;
            }
        }

        return null;
    }

    /**
     * มูลค่าเวนคืนล่าสุด (สตางค์) ที่ไม่เกินวันที่ระบุ = ตัวเลขที่นับเป็นทรัพย์สิน
     */
    public function currentCashValue(?CarbonInterface $asOf = null): int
    {
        return (int) ($this->values()
            ->whereDate('as_of_date', '<=', ($asOf ?? now())->toDateString())
            ->orderByDesc('as_of_date')
            ->value('cash_value') ?? 0);
    }
}
