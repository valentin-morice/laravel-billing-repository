<?php

use Illuminate\Console\Command;
use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ProductChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatSummaryAction;

afterEach(function () {
    m::close();
});

it('displays no changes message when changeset is empty', function () {
    $action = new FormatSummaryAction;
    $command = m::mock(Command::class);

    $changeSet = new ChangeSet([], []);

    $command->shouldReceive('line')
        ->once()
        ->with('Summary: No changes');

    $action->handle($command, $changeSet);
});

it('displays summary for created items only', function () {
    $action = new FormatSummaryAction;
    $command = m::mock(Command::class);

    $productChange = new ProductChange(
        productKey: 'test',
        type: ChangeTypeEnum::Created,
        definition: null,
        existingProduct: null,
        resultProduct: null,
    );

    $priceChange = new PriceChange(
        productKey: 'test',
        priceKey: 'monthly',
        type: ChangeTypeEnum::Created,
        definition: null,
        existingPrice: null,
        resultPrice: null,
    );

    $changeSet = new ChangeSet([$productChange], [$priceChange]);

    $command->shouldReceive('line')
        ->once()
        ->with('Summary: 2 created');

    $action->handle($command, $changeSet);
});

it('displays summary for updated items only', function () {
    $action = new FormatSummaryAction;
    $command = m::mock(Command::class);

    $productChange = new ProductChange(
        productKey: 'test',
        type: ChangeTypeEnum::Updated,
        definition: null,
        existingProduct: null,
        resultProduct: null,
    );

    $changeSet = new ChangeSet([$productChange], []);

    $command->shouldReceive('line')
        ->once()
        ->with('Summary: 1 updated');

    $action->handle($command, $changeSet);
});

it('displays summary for archived items only', function () {
    $action = new FormatSummaryAction;
    $command = m::mock(Command::class);

    $priceChange = new PriceChange(
        productKey: 'test',
        priceKey: 'monthly',
        type: ChangeTypeEnum::Archived,
        definition: null,
        existingPrice: null,
        resultPrice: null,
    );

    $changeSet = new ChangeSet([], [$priceChange]);

    $command->shouldReceive('line')
        ->once()
        ->with('Summary: 1 archived');

    $action->handle($command, $changeSet);
});

it('displays summary for unchanged items only', function () {
    $action = new FormatSummaryAction;
    $command = m::mock(Command::class);

    $productChange = new ProductChange(
        productKey: 'test',
        type: ChangeTypeEnum::Unchanged,
        definition: null,
        existingProduct: null,
        resultProduct: null,
    );

    $changeSet = new ChangeSet([$productChange], []);

    $command->shouldReceive('line')
        ->once()
        ->with('Summary: 1 unchanged');

    $action->handle($command, $changeSet);
});

it('displays summary for mixed changes', function () {
    $action = new FormatSummaryAction;
    $command = m::mock(Command::class);

    $productChanges = [
        new ProductChange('p1', ChangeTypeEnum::Created, null, null, null),
        new ProductChange('p2', ChangeTypeEnum::Updated, null, null, null),
        new ProductChange('p3', ChangeTypeEnum::Archived, null, null, null),
    ];

    $priceChanges = [
        new PriceChange('p1', 'monthly', ChangeTypeEnum::Created, null, null, null),
        new PriceChange('p2', 'monthly', ChangeTypeEnum::Unchanged, null, null, null),
    ];

    $changeSet = new ChangeSet($productChanges, $priceChanges);

    $command->shouldReceive('line')
        ->once()
        ->with('Summary: 2 created, 1 updated, 1 archived, 1 unchanged');

    $action->handle($command, $changeSet);
});

it('combines product and price counts correctly', function () {
    $action = new FormatSummaryAction;
    $command = m::mock(Command::class);

    $productChanges = [
        new ProductChange('p1', ChangeTypeEnum::Created, null, null, null),
        new ProductChange('p2', ChangeTypeEnum::Created, null, null, null),
    ];

    $priceChanges = [
        new PriceChange('p1', 'monthly', ChangeTypeEnum::Created, null, null, null),
        new PriceChange('p1', 'yearly', ChangeTypeEnum::Updated, null, null, null),
    ];

    $changeSet = new ChangeSet($productChanges, $priceChanges);

    // 3 created (2 products + 1 price), 1 updated (1 price)
    $command->shouldReceive('line')
        ->once()
        ->with('Summary: 3 created, 1 updated');

    $action->handle($command, $changeSet);
});

it('excludes zero counts from summary', function () {
    $action = new FormatSummaryAction;
    $command = m::mock(Command::class);

    $productChanges = [
        new ProductChange('p1', ChangeTypeEnum::Created, null, null, null),
    ];

    $changeSet = new ChangeSet($productChanges, []);

    // Should only show "1 created", not "0 updated, 0 archived, 0 unchanged"
    $command->shouldReceive('line')
        ->once()
        ->with('Summary: 1 created');

    $action->handle($command, $changeSet);
});
