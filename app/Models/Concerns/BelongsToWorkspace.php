<?php

namespace App\Models\Concerns;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToWorkspace
{
    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForWorkspace(Builder $query, Workspace|int $workspace): void
    {
        $query->where($this->qualifyColumn('workspace_id'), $workspace instanceof Workspace ? $workspace->getKey() : $workspace);
    }
}
