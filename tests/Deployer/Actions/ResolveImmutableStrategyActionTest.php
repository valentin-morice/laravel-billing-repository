<?php

use Illuminate\Console\Command;
use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy;
use ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\ResolveImmutableStrategyAction;
use ValentinMorice\LaravelBillingRepository\Exceptions\Deployer\DeploymentCancelledException;

afterEach(function () {
    m::close();
});

it('returns unchanged price change when no immutable changes', function () {
    $action = new ResolveImmutableStrategyAction;
    $command = m::mock(Command::class);

    $change = new PriceChange(
        productKey: 'premium',
        priceKey: 'monthly',
        type: ChangeTypeEnum::Updated,
        definition: new PriceDefinition(amount: 1000),
        existingPrice: null,
        resultPrice: null,
        changes: ['nickname' => ['old' => 'Old', 'new' => 'New']],
        hasImmutableChanges: false,
    );

    $result = $action->handle($command, $change, ['monthly', 'yearly']);

    expect($result)->toBe($change)
        ->and($result->strategy)->toBeNull()
        ->and($result->newPriceKey)->toBeNull();
});

it('resolves archive strategy', function () {
    $action = new ResolveImmutableStrategyAction;
    $command = m::mock(Command::class);

    $command->shouldReceive('newLine')->atLeast()->once();
    $command->shouldReceive('warn')->once();
    $command->shouldReceive('line')->atLeast()->once();
    $command->shouldReceive('choice')
        ->once()
        ->andReturn('archive');

    $change = new PriceChange(
        productKey: 'premium',
        priceKey: 'monthly',
        type: ChangeTypeEnum::Updated,
        definition: new PriceDefinition(amount: 1299),
        existingPrice: null,
        resultPrice: null,
        changes: ['amount' => ['old' => 999, 'new' => 1299]],
        hasImmutableChanges: true,
        immutableFieldsClass: ImmutablePriceFields::class,
    );

    $result = $action->handle($command, $change, ['monthly', 'yearly']);

    expect($result->strategy)->toBe(ImmutableFieldStrategy::Archive)
        ->and($result->newPriceKey)->toBeNull();
});

it('resolves duplicate strategy with custom key', function () {
    $action = new ResolveImmutableStrategyAction;
    $command = m::mock(Command::class);

    $command->shouldReceive('newLine')->atLeast()->once();
    $command->shouldReceive('warn')->once();
    $command->shouldReceive('line')->atLeast()->once();
    $command->shouldReceive('choice')
        ->once()
        ->andReturn('duplicate');
    $command->shouldReceive('ask')
        ->once()
        ->with('Enter key for new price', 'monthly_1')
        ->andReturn('monthly_v2');

    $change = new PriceChange(
        productKey: 'premium',
        priceKey: 'monthly',
        type: ChangeTypeEnum::Updated,
        definition: new PriceDefinition(amount: 1299),
        existingPrice: null,
        resultPrice: null,
        changes: ['amount' => ['old' => 999, 'new' => 1299]],
        hasImmutableChanges: true,
        immutableFieldsClass: ImmutablePriceFields::class,
    );

    $result = $action->handle($command, $change, ['monthly', 'yearly']);

    expect($result->strategy)->toBe(ImmutableFieldStrategy::Duplicate)
        ->and($result->newPriceKey)->toBe('monthly_v2');
});

it('throws exception on cancel strategy', function () {
    $action = new ResolveImmutableStrategyAction;
    $command = m::mock(Command::class);

    $command->shouldReceive('newLine')->atLeast()->once();
    $command->shouldReceive('warn')->once();
    $command->shouldReceive('line')->atLeast()->once();
    $command->shouldReceive('choice')
        ->once()
        ->andReturn('cancel');

    $change = new PriceChange(
        productKey: 'premium',
        priceKey: 'monthly',
        type: ChangeTypeEnum::Updated,
        definition: new PriceDefinition(amount: 1299),
        existingPrice: null,
        resultPrice: null,
        changes: ['amount' => ['old' => 999, 'new' => 1299]],
        hasImmutableChanges: true,
        immutableFieldsClass: ImmutablePriceFields::class,
    );

    expect(fn () => $action->handle($command, $change, ['monthly', 'yearly']))
        ->toThrow(DeploymentCancelledException::class, 'Deployment cancelled by user');
});

it('generates default key with incrementing suffix', function () {
    $action = new ResolveImmutableStrategyAction;
    $command = m::mock(Command::class);

    $command->shouldReceive('newLine')->atLeast()->once();
    $command->shouldReceive('warn')->once();
    $command->shouldReceive('line')->atLeast()->once();
    $command->shouldReceive('choice')
        ->once()
        ->andReturn('duplicate');
    // Expect default key to be monthly_2 since monthly_1 already exists
    $command->shouldReceive('ask')
        ->once()
        ->with('Enter key for new price', 'monthly_2')
        ->andReturn('monthly_2');

    $change = new PriceChange(
        productKey: 'premium',
        priceKey: 'monthly',
        type: ChangeTypeEnum::Updated,
        definition: new PriceDefinition(amount: 1299),
        existingPrice: null,
        resultPrice: null,
        changes: ['amount' => ['old' => 999, 'new' => 1299]],
        hasImmutableChanges: true,
        immutableFieldsClass: ImmutablePriceFields::class,
    );

    // monthly_1 already exists, so default should be monthly_2
    $result = $action->handle($command, $change, ['monthly', 'yearly', 'monthly_1']);

    expect($result->newPriceKey)->toBe('monthly_2');
});

it('rejects duplicate key that already exists', function () {
    $action = new ResolveImmutableStrategyAction;
    $command = m::mock(Command::class);

    $command->shouldReceive('newLine')->atLeast()->once();
    $command->shouldReceive('warn')->atLeast()->once();
    $command->shouldReceive('line')->atLeast()->once();
    $command->shouldReceive('choice')
        ->twice()
        ->andReturn('duplicate');
    $command->shouldReceive('error')
        ->once()
        ->with("Key 'yearly' already exists. Please try again.");
    $command->shouldReceive('ask')
        ->twice()
        ->andReturn('yearly', 'monthly_new'); // First attempt fails, second succeeds

    $change = new PriceChange(
        productKey: 'premium',
        priceKey: 'monthly',
        type: ChangeTypeEnum::Updated,
        definition: new PriceDefinition(amount: 1299),
        existingPrice: null,
        resultPrice: null,
        changes: ['amount' => ['old' => 999, 'new' => 1299]],
        hasImmutableChanges: true,
        immutableFieldsClass: ImmutablePriceFields::class,
    );

    $result = $action->handle($command, $change, ['monthly', 'yearly']);

    expect($result->newPriceKey)->toBe('monthly_new');
});
