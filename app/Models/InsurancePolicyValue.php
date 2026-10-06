<?php

namespace App\Models;

use Database\Factories\InsurancePolicyValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['insurance_policy_id', 'as_of_date', 'cash_value'])]
class InsurancePolicyValue extends Model
{
    /** @use HasFactory<InsurancePolicyValueFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'as_of_date' => 'date',
            'cash_value' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<InsurancePolicy, $this>
     */
    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class);
    }
}
