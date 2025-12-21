<?php

use Mockery as m;
use ValentinMorice\LaravelStripeRepository\Actions\Product\UpdateAction;
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

it('updates product name when name changes', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Old Name',
        'description' => 'Same description',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('update')
        ->once()
        ->with('prod_123', ['name' => 'New Name'])
        ->andReturn((object) ['id' => 'prod_123', 'name' => 'New Name']);

    $action = new UpdateAction($client);
    $definition = new ProductDefinition(
        name: 'New Name',
        prices: [],
        description: 'Same description'
    );

    $result = $action->handle($product, $definition);

    expect($result->name)->toBe('New Name')
        ->and($result->description)->toBe('Same description');
});

it('updates product description when description changes', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Same Name',
        'description' => 'Old description',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('update')
        ->once()
        ->with('prod_123', ['description' => 'New description'])
        ->andReturn((object) ['id' => 'prod_123']);

    $action = new UpdateAction($client);
    $definition = new ProductDefinition(
        name: 'Same Name',
        prices: [],
        description: 'New description'
    );

    $result = $action->handle($product, $definition);

    expect($result->description)->toBe('New description')
        ->and($result->name)->toBe('Same Name');
});

it('updates both name and description when both change', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Old Name',
        'description' => 'Old description',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('update')
        ->once()
        ->with('prod_123', ['name' => 'New Name', 'description' => 'New description'])
        ->andReturn((object) ['id' => 'prod_123']);

    $action = new UpdateAction($client);
    $definition = new ProductDefinition(
        name: 'New Name',
        prices: [],
        description: 'New description'
    );

    $result = $action->handle($product, $definition);

    expect($result->name)->toBe('New Name')
        ->and($result->description)->toBe('New description');
});

it('returns product unchanged when nothing changes', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Same Name',
        'description' => 'Same description',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldNotReceive('update');

    $action = new UpdateAction($client);
    $definition = new ProductDefinition(
        name: 'Same Name',
        prices: [],
        description: 'Same description'
    );

    $result = $action->handle($product, $definition);

    expect($result->id)->toBe($product->id)
        ->and($result->name)->toBe('Same Name')
        ->and($result->description)->toBe('Same description');
});

it('handles null description correctly', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Same Name',
        'description' => null,
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldNotReceive('update');

    $action = new UpdateAction($client);
    $definition = new ProductDefinition(
        name: 'Same Name',
        prices: []
    );

    $result = $action->handle($product, $definition);

    expect($result->description)->toBeNull();
});

it('throws exception when Stripe API fails', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Old Name',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('update')
        ->once()
        ->andThrow(new \Exception('Stripe API error'));

    $action = new UpdateAction($client);
    $definition = new ProductDefinition(
        name: 'New Name',
        prices: []
    );

    expect(fn () => $action->handle($product, $definition))
        ->toThrow(\Exception::class, 'Stripe API error');

    // Verify database was not updated
    $product->refresh();
    expect($product->name)->toBe('Old Name');
});
