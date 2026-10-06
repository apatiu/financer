<?php

use App\Enums\WorkspaceRole;
use App\Models\User;

test('a new user gets a personal workspace as its owner', function () {
    $user = User::factory()->create();

    $workspace = $user->workspaces()->sole();

    expect($workspace->base_currency)->toBe('THB')
        ->and($workspace->pivot->role)->toBe(WorkspaceRole::Owner->value)
        ->and($workspace->owner()->is($user))->toBeTrue();
});
