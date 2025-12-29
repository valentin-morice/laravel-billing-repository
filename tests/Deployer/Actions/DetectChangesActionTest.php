<?php

use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;

it('detects no changes when objects are identical', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['name' => 'Test', 'description' => 'Desc'];
    $definition = (object) ['name' => 'Test', 'description' => 'Desc'];

    $changes = $action->handle($existing, $definition, ['name', 'description']);

    expect($changes)->toBeEmpty();
});

it('detects single field change', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['name' => 'Old Name', 'description' => 'Same'];
    $definition = (object) ['name' => 'New Name', 'description' => 'Same'];

    $changes = $action->handle($existing, $definition, ['name', 'description']);

    expect($changes)->toBe([
        'name' => ['old' => 'Old Name', 'new' => 'New Name'],
    ]);
});

it('detects multiple field changes', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['name' => 'Old Name', 'description' => 'Old Desc', 'active' => true];
    $definition = (object) ['name' => 'New Name', 'description' => 'New Desc', 'active' => false];

    $changes = $action->handle($existing, $definition, ['name', 'description', 'active']);

    expect($changes)->toBe([
        'name' => ['old' => 'Old Name', 'new' => 'New Name'],
        'description' => ['old' => 'Old Desc', 'new' => 'New Desc'],
        'active' => ['old' => true, 'new' => false],
    ]);
});

it('detects changes with null values', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['name' => 'Test', 'description' => null];
    $definition = (object) ['name' => 'Test', 'description' => 'New Desc'];

    $changes = $action->handle($existing, $definition, ['name', 'description']);

    expect($changes)->toBe([
        'description' => ['old' => null, 'new' => 'New Desc'],
    ]);
});

it('detects changes from value to null', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['name' => 'Test', 'description' => 'Old Desc'];
    $definition = (object) ['name' => 'Test', 'description' => null];

    $changes = $action->handle($existing, $definition, ['name', 'description']);

    expect($changes)->toBe([
        'description' => ['old' => 'Old Desc', 'new' => null],
    ]);
});

it('detects changes with array values', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['recurring' => ['interval' => 'month'], 'amount' => 1000];
    $definition = (object) ['recurring' => ['interval' => 'year'], 'amount' => 1000];

    $changes = $action->handle($existing, $definition, ['recurring', 'amount']);

    expect($changes)->toBe([
        'recurring' => ['old' => ['interval' => 'month'], 'new' => ['interval' => 'year']],
    ]);
});

it('only checks specified fields', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['name' => 'Old', 'description' => 'Old Desc', 'active' => true];
    $definition = (object) ['name' => 'New', 'description' => 'New Desc', 'active' => false];

    // Only check 'name' field
    $changes = $action->handle($existing, $definition, ['name']);

    expect($changes)->toBe([
        'name' => ['old' => 'Old', 'new' => 'New'],
    ]);
});

it('handles empty field list', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['name' => 'Old', 'description' => 'Old Desc'];
    $definition = (object) ['name' => 'New', 'description' => 'New Desc'];

    $changes = $action->handle($existing, $definition, []);

    expect($changes)->toBeEmpty();
});

it('handles numeric values', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['amount' => 1000, 'currency' => 'usd'];
    $definition = (object) ['amount' => 2000, 'currency' => 'usd'];

    $changes = $action->handle($existing, $definition, ['amount', 'currency']);

    expect($changes)->toBe([
        'amount' => ['old' => 1000, 'new' => 2000],
    ]);
});

it('handles boolean values', function () {
    $action = new DetectChangesAction;

    $existing = (object) ['active' => true];
    $definition = (object) ['active' => false];

    $changes = $action->handle($existing, $definition, ['active']);

    expect($changes)->toBe([
        'active' => ['old' => true, 'new' => false],
    ]);
});
