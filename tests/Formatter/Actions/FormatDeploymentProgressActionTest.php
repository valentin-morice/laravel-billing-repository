<?php

use Illuminate\Console\Command;
use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ProductChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatDeploymentProgressAction;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

afterEach(function () {
    m::close();
});

it('formats created product message', function () {
    $action = new FormatDeploymentProgressAction;
    $command = m::mock(Command::class);

    $product = new BillingProduct(['provider_id' => 'prod_123']);

    $change = new ProductChange(
        productKey: 'premium-plan',
        type: ChangeTypeEnum::Created,
        definition: null,
        existingProduct: null,
        resultProduct: $product,
    );

    $command->shouldReceive('info')
        ->once()
        ->with('✓ Created product: premium-plan (prod_123)');

    $action->handle($command, $change);
});

it('formats updated product message', function () {
    $action = new FormatDeploymentProgressAction;
    $command = m::mock(Command::class);

    $product = new BillingProduct(['provider_id' => 'prod_456']);

    $change = new ProductChange(
        productKey: 'basic-plan',
        type: ChangeTypeEnum::Updated,
        definition: null,
        existingProduct: null,
        resultProduct: $product,
    );

    $command->shouldReceive('info')
        ->once()
        ->with('✓ Updated product: basic-plan (prod_456)');

    $action->handle($command, $change);
});

it('formats archived product message', function () {
    $action = new FormatDeploymentProgressAction;
    $command = m::mock(Command::class);

    $product = new BillingProduct(['provider_id' => 'prod_789']);

    $change = new ProductChange(
        productKey: 'legacy-plan',
        type: ChangeTypeEnum::Archived,
        definition: null,
        existingProduct: null,
        resultProduct: $product,
    );

    $command->shouldReceive('info')
        ->once()
        ->with('✓ Archived product: legacy-plan (prod_789)');

    $action->handle($command, $change);
});

it('formats product message without provider id when null', function () {
    $action = new FormatDeploymentProgressAction;
    $command = m::mock(Command::class);

    $change = new ProductChange(
        productKey: 'test-plan',
        type: ChangeTypeEnum::Created,
        definition: null,
        existingProduct: null,
        resultProduct: null,
    );

    $command->shouldReceive('info')
        ->once()
        ->with('✓ Created product: test-plan');

    $action->handle($command, $change);
});

it('formats created price message', function () {
    $action = new FormatDeploymentProgressAction;
    $command = m::mock(Command::class);

    $price = new BillingPrice(['provider_id' => 'price_123']);

    $change = new PriceChange(
        productKey: 'premium-plan',
        priceType: 'monthly',
        type: ChangeTypeEnum::Created,
        definition: null,
        existingPrice: null,
        resultPrice: $price,
    );

    $command->shouldReceive('info')
        ->once()
        ->with('✓ Created price: premium-plan.monthly (price_123)');

    $action->handle($command, $change);
});

it('formats updated price message', function () {
    $action = new FormatDeploymentProgressAction;
    $command = m::mock(Command::class);

    $price = new BillingPrice(['provider_id' => 'price_456']);

    $change = new PriceChange(
        productKey: 'basic-plan',
        priceType: 'yearly',
        type: ChangeTypeEnum::Updated,
        definition: null,
        existingPrice: null,
        resultPrice: $price,
    );

    $command->shouldReceive('info')
        ->once()
        ->with('✓ Updated price: basic-plan.yearly (price_456)');

    $action->handle($command, $change);
});

it('formats archived price message', function () {
    $action = new FormatDeploymentProgressAction;
    $command = m::mock(Command::class);

    $price = new BillingPrice(['provider_id' => 'price_789']);

    $change = new PriceChange(
        productKey: 'legacy-plan',
        priceType: 'monthly',
        type: ChangeTypeEnum::Archived,
        definition: null,
        existingPrice: null,
        resultPrice: $price,
    );

    $command->shouldReceive('info')
        ->once()
        ->with('✓ Archived price: legacy-plan.monthly (price_789)');

    $action->handle($command, $change);
});

it('formats price message without provider id when null', function () {
    $action = new FormatDeploymentProgressAction;
    $command = m::mock(Command::class);

    $change = new PriceChange(
        productKey: 'test-plan',
        priceType: 'monthly',
        type: ChangeTypeEnum::Created,
        definition: null,
        existingPrice: null,
        resultPrice: null,
    );

    $command->shouldReceive('info')
        ->once()
        ->with('✓ Created price: test-plan.monthly');

    $action->handle($command, $change);
});
