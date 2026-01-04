<?php

use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\ProductResourceInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product\CreateAction;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
});

afterEach(function () {
    m::close();
});

it('creates product and stores in database', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('create')
        ->once()
        ->with('Test Product', null, null, null, null)
        ->andReturn('prod_123');

    $action = new CreateAction($client);
    $definition = new ProductDefinition(
        name: 'Test Product',
        prices: []
    );

    $result = $action->handle('test_product', $definition);

    expect($result)->toBeInstanceOf(BillingProduct::class)
        ->and($result->key)->toBe('test_product')
        ->and($result->provider_id)->toBe('prod_123')
        ->and($result->name)->toBe('Test Product')
        ->and($result->description)->toBeNull()
        ->and($result->active)->toBeTrue()
        ->and(BillingProduct::count())->toBe(1);
});

it('creates product with description', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('create')
        ->once()
        ->with('Premium Product', 'A premium subscription product', null, null, null)
        ->andReturn('prod_456');

    $action = new CreateAction($client);
    $definition = new ProductDefinition(
        name: 'Premium Product',
        prices: [],
        description: 'A premium subscription product'
    );

    $result = $action->handle('premium_product', $definition);

    expect($result->description)->toBe('A premium subscription product')
        ->and($result->provider_id)->toBe('prod_456');
});

it('throws exception when provider API fails', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
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
    expect(BillingProduct::count())->toBe(0);
});

it('returns existing product when duplicate provider_id is detected', function () {
    // Create existing product in database
    $existingProduct = BillingProduct::factory()->create([
        'key' => 'existing_product',
        'provider_id' => 'prod_duplicate',
        'name' => 'Existing Product',
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    // Stripe returns same provider_id
    $productResource->shouldReceive('create')
        ->once()
        ->andReturn('prod_duplicate');

    $action = new CreateAction($client);
    $definition = new ProductDefinition(
        name: 'New Product Attempt',
        prices: []
    );

    $result = $action->handle('new_product_key', $definition);

    // Should return the existing product instead of creating a new one
    expect($result->id)->toBe($existingProduct->id)
        ->and($result->provider_id)->toBe('prod_duplicate')
        ->and(BillingProduct::count())->toBe(1); // Still only 1 product
});
