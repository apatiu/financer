<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\NetWorthSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'date', 'currency', 'cash', 'investment_cash', 'investments', 'insurance', 'fixed_assets', 'total_assets', 'loans', 'credit_card_debt', 'total_liabilities', 'net_worth', 'unconverted_items'])]
class NetWorthSnapshot extends Model
{
    /** @use HasFactory<NetWorthSnapshotFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'cash' => 'integer',
            'investment_cash' => 'integer',
            'investments' => 'integer',
            'insurance' => 'integer',
            'fixed_assets' => 'integer',
            'total_assets' => 'integer',
            'loans' => 'integer',
            'credit_card_debt' => 'integer',
            'total_liabilities' => 'integer',
            'net_worth' => 'integer',
            'unconverted_items' => 'integer',
        ];
    }
}
