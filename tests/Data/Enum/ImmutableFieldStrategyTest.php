<?php

use ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy;

it('has correct enum values', function () {
    expect(ImmutableFieldStrategy::Archive->value)->toBe('archive')
        ->and(ImmutableFieldStrategy::Duplicate->value)->toBe('duplicate')
        ->and(ImmutableFieldStrategy::Cancel->value)->toBe('cancel');
});

it('can be created from string value', function () {
    expect(ImmutableFieldStrategy::from('archive'))->toBe(ImmutableFieldStrategy::Archive)
        ->and(ImmutableFieldStrategy::from('duplicate'))->toBe(ImmutableFieldStrategy::Duplicate)
        ->and(ImmutableFieldStrategy::from('cancel'))->toBe(ImmutableFieldStrategy::Cancel);
});

it('provides human-readable descriptions', function () {
    expect(ImmutableFieldStrategy::Archive->description())
        ->toBe('Archive old price, create new with same key')
        ->and(ImmutableFieldStrategy::Duplicate->description())
        ->toBe('Keep old price active, create new price')
        ->and(ImmutableFieldStrategy::Cancel->description())
        ->toBe('Abort deployment');
});
