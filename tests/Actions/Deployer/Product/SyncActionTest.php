<?php

use Mockery as m;
use ValentinMorice\LaravelStripeRepository\Actions\Deployer\Product\SyncAction;
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

it('creates new product when it does not exist', function () {
    // Mock Stripe client
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('create')
        ->once()
        ->with('Test Product', null)
        ->andReturn('prod_123');

    // Create action and execute
    $action = new SyncAction($client);
    $definition = new ProductDefinition(name: 'Test Product', prices: []);

    $product = $action->handle('test_product', $definition);

    // Assert product was created
    expect($product)->toBeInstanceOf(StripeProduct::class)
        ->and($product->key)->toBe('test_product')
        ->and($product->stripe_id)->toBe('prod_123')
        ->and($product->name)->toBe('Test Product')
        ->and($product->active)->toBeTrue()
        ->and($product->wasRecentlyCreated)->toBeTrue();

    // Assert database
    expect(StripeProduct::count())->toBe(1);
});

it('creates product with description', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('create')
        ->once()
        ->with('Product Name', 'Product description')
        ->andReturn('prod_456');

    $action = new SyncAction($client);
    $definition = new ProductDefinition(
        name: 'Product Name',
        prices: [],
        description: 'Product description'
    );

    $product = $action->handle('product_key', $definition);

    expect($product->stripe_id)->toBe('prod_456');
});

it('retrieves existing product without creating new one', function () {
    // Create existing product in database
    $existingProduct = StripeProduct::create([
        'key' => 'existing_product',
        'stripe_id' => 'prod_existing',
        'name' => 'Existing Product',
        'active' => true,
    ]);

    // Mock Stripe client
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('retrieve')
        ->once()
        ->with('prod_existing')
        ->andReturn((object) ['id' => 'prod_existing']);

    $productResource->shouldNotReceive('create');

    // Execute action
    $action = new SyncAction($client);
    $definition = new ProductDefinition(name: 'Existing Product', prices: []);

    $product = $action->handle('existing_product', $definition);

    // Assert same product was returned
    expect($product->id)->toBe($existingProduct->id)
        ->and($product->wasRecentlyCreated)->toBeFalse()
        ->and(StripeProduct::count())->toBe(1);
});

it('returns existing product by key match', function () {
    // Create product
    StripeProduct::create([
        'key' => 'my_product',
        'stripe_id' => 'prod_abc',
        'name' => 'My Product',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('retrieve')
        ->once()
        ->with('prod_abc')
        ->andReturn((object) ['id' => 'prod_abc']);

    $action = new SyncAction($client);
    $definition = new ProductDefinition(name: 'My Product', prices: []);

    $product = $action->handle('my_product', $definition);

    expect($product->key)->toBe('my_product')
        ->and($product->stripe_id)->toBe('prod_abc');
});
