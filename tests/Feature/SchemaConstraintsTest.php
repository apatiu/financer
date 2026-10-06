<?php

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Database\UniqueConstraintViolationException;

test('top-level categories cannot share a name within a workspace', function () {
    $workspace = Workspace::factory()->create();
    Category::factory()->for($workspace)->create(['name' => 'อาหาร']);

    expect(fn () => Category::factory()->for($workspace)->create(['name' => 'อาหาร']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('the same category name is allowed under different parents and workspaces', function () {
    $workspace = Workspace::factory()->create();
    $food = Category::factory()->for($workspace)->create(['name' => 'อาหาร']);
    $travel = Category::factory()->for($workspace)->create(['name' => 'เดินทาง']);

    Category::factory()->for($workspace)->create(['name' => 'อื่นๆ', 'parent_id' => $food->id]);
    Category::factory()->for($workspace)->create(['name' => 'อื่นๆ', 'parent_id' => $travel->id]);
    Category::factory()->create(['name' => 'อาหาร']);

    expect(Category::count())->toBe(5);
});

test('shared assets cannot be duplicated even without an exchange', function () {
    Asset::factory()->create(['type' => AssetType::Gold, 'symbol' => 'GOLD965', 'exchange' => null]);

    expect(fn () => Asset::factory()->create(['type' => AssetType::Gold, 'symbol' => 'GOLD965', 'exchange' => null]))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('a workspace asset may reuse a shared asset symbol', function () {
    $workspace = Workspace::factory()->create();
    Asset::factory()->create(['symbol' => 'PTT']);

    Asset::factory()->create(['symbol' => 'PTT', 'workspace_id' => $workspace->id]);

    expect(Asset::where('symbol', 'PTT')->count())->toBe(2);
});
