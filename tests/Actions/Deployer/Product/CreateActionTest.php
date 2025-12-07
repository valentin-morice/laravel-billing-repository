<?php

use Mockery as m;
use ValentinMorice\LaravelStripeRepository\Actions\Deployer\Product\CreateAction;
use ValentinMorice\LaravelStripeRepository\Contracts\ProductResourceInterface;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\ProductDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
});

afterEach(function () {
    m::close();
});

it('creates product in Stripe and database', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('create')
        ->once()
        ->with('Test Product', null)
        ->andReturn('prod_123');

    $action = new CreateAction($client);
    $definition = new ProductDefinition(
        name: 'Test Product',
        prices: []
    );

    $result = $action->handle('test_product', $definition);

    expect($result)->toBeInstanceOf(StripeProduct::class)
        ->and($result->key)->toBe('test_product')
        ->and($result->stripe_id)->toBe('prod_123')
        ->and($result->name)->toBe('Test Product')
        ->and($result->description)->toBeNull()
        ->and($result->active)->toBeTrue()
        ->and(StripeProduct::count())->toBe(1);
});

it('creates product with description', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('create')
        ->once()
        ->with('Premium Product', 'A premium subscription product')
        ->andReturn('prod_456');

    $action = new CreateAction($client);
    $definition = new ProductDefinition(
        name: 'Premium Product',
        prices: [],
        description: 'A premium subscription product'
    );

    $result = $action->handle('premium_product', $definition);

    expect($result->description)->toBe('A premium subscription product')
        ->and($result->stripe_id)->toBe('prod_456');
});

it('throws exception when Stripe API fails', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('create')
        ->once()
        ->andThrow(new \Exception('Stripe API error'));

    $action = new CreateAction($client);
    $definition = new ProductDefinition(
        name: 'Test Product',
        prices: []
    );

    expect(fn () => $action->handle('test_product', $definition))
        ->toThrow(\Exception::class, 'Stripe API error');

    // Verify no database record was created
    expect(StripeProduct::count())->toBe(0);
});
