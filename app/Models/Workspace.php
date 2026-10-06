<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'base_currency'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')->withPivot('role')->withTimestamps();
    }

    public function owner(): ?User
    {
        return $this->users()->wherePivot('role', WorkspaceRole::Owner->value)->first();
    }

    public static function createPersonalFor(User $user): self
    {
        return self::createFor($user, ['name' => "สมุดบัญชีของ {$user->name}"]);
    }

    /**
     * @param  array{name: string, base_currency?: string}  $attributes
     */
    public static function createFor(User $user, array $attributes): self
    {
        $workspace = self::create($attributes);
        $workspace->users()->attach($user, ['role' => WorkspaceRole::Owner->value]);

        return $workspace;
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<AssetTrade, $this>
     */
    public function assetTrades(): HasMany
    {
        return $this->hasMany(AssetTrade::class);
    }

    /**
     * @return HasMany<ExchangeRate, $this>
     */
    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    /**
     * @return HasMany<NetWorthSnapshot, $this>
     */
    public function netWorthSnapshots(): HasMany
    {
        return $this->hasMany(NetWorthSnapshot::class);
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * @return HasMany<FixedAsset, $this>
     */
    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    /**
     * @return HasMany<Liability, $this>
     */
    public function liabilities(): HasMany
    {
        return $this->hasMany(Liability::class);
    }

    /**
     * @return HasMany<InsurancePolicy, $this>
     */
    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    /**
     * @return HasMany<Holding, $this>
     */
    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }
}
