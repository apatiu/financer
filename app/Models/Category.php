<?php

namespace App\Models;

use App\Enums\CategoryType;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'parent_id', 'name', 'type'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            $category->parent_key = $category->parent_id ?? 0;
        });
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
