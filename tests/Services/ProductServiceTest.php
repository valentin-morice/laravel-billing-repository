<?php

use Mockery as m;
use ValentinMorice\LaravelStripeRepository\Contracts\ProductResourceInterface;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\ProductDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;
use ValentinMorice\LaravelStripeRepository\Services\ProductService;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
});

afterEach(function () {
    m::close();
});

it('creates new product when it does not exist', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('create')
        ->once()
        ->with('Test Product', null)
        ->andReturn('prod_123');

    $service = new ProductService($client);
    $definition = new ProductDefinition(name: 'Test Product', prices: []);

    $result = $service->sync('test_product', $definition);

    expect($result)->toBeArray()
        ->and($result['action'])->toBe('created')
        ->and($result['product'])->toBeInstanceOf(StripeProduct::class)
        ->and($result['product']->key)->toBe('test_product')
        ->and($result['product']->stripe_id)->toBe('prod_123')
        ->and($result['product']->name)->toBe('Test Product')
        ->and($result['product']->description)->toBeNull()
        ->and(StripeProduct::count())->toBe(1);
});

it('creates product with description', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('create')
        ->once()
        ->with('Product Name', 'Product description')
        ->andReturn('prod_456');

    $service = new ProductService($client);
    $definition = new ProductDefinition(
        name: 'Product Name',
        prices: [],
        description: 'Product description'
    );

    $result = $service->sync('product_key', $definition);

    expect($result['action'])->toBe('created')
        ->and($result['product']->stripe_id)->toBe('prod_456')
        ->and($result['product']->description)->toBe('Product description');
});

it('returns unchanged when product already exists with same data', function () {
    StripeProduct::create([
        'key' => 'existing_product',
        'stripe_id' => 'prod_existing',
        'name' => 'Existing Product',
        'description' => 'Same description',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldNotReceive('create');
    $productResource->shouldNotReceive('update');

    $service = new ProductService($client);
    $definition = new ProductDefinition(
        name: 'Existing Product',
        prices: [],
        description: 'Same description'
    );

    $result = $service->sync('existing_product', $definition);

    expect($result['action'])->toBe('unchanged')
        ->and(StripeProduct::count())->toBe(1);
});

it('updates product when name changes', function () {
    StripeProduct::create([
        'key' => 'my_product',
        'stripe_id' => 'prod_abc',
        'name' => 'Old Name',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('update')
        ->once()
        ->with('prod_abc', ['name' => 'New Name'])
        ->andReturn((object) ['id' => 'prod_abc', 'name' => 'New Name']);

    $productResource->shouldNotReceive('create');

    $service = new ProductService($client);
    $definition = new ProductDefinition(name: 'New Name', prices: []);

    $result = $service->sync('my_product', $definition);

    expect($result['action'])->toBe('updated')
        ->and($result['product']->name)->toBe('New Name');
});

it('updates product when description changes', function () {
    StripeProduct::create([
        'key' => 'my_product',
        'stripe_id' => 'prod_xyz',
        'name' => 'Product Name',
        'description' => 'Old description',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('update')
        ->once()
        ->with('prod_xyz', ['description' => 'New description'])
        ->andReturn((object) ['id' => 'prod_xyz']);

    $service = new ProductService($client);
    $definition = new ProductDefinition(
        name: 'Product Name',
        prices: [],
        description: 'New description'
    );

    $result = $service->sync('my_product', $definition);

    expect($result['action'])->toBe('updated')
        ->and($result['product']->description)->toBe('New description');
});

it('updates product when both name and description change', function () {
    StripeProduct::create([
        'key' => 'my_product',
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

    $service = new ProductService($client);
    $definition = new ProductDefinition(
        name: 'New Name',
        prices: [],
        description: 'New description'
    );

    $result = $service->sync('my_product', $definition);

    expect($result['action'])->toBe('updated')
        ->and($result['product']->name)->toBe('New Name')
        ->and($result['product']->description)->toBe('New description');
});

it('archives products not in configured keys list', function () {
    StripeProduct::create([
        'key' => 'product_1',
        'stripe_id' => 'prod_1',
        'name' => 'Product 1',
        'active' => true,
    ]);

    StripeProduct::create([
        'key' => 'product_2',
        'stripe_id' => 'prod_2',
        'name' => 'Product 2',
        'active' => true,
    ]);

    StripeProduct::create([
        'key' => 'product_3',
        'stripe_id' => 'prod_3',
        'name' => 'Product 3',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    // Expect product_2 and product_3 to be archived
    $productResource->shouldReceive('archive')
        ->once()
        ->with('prod_2')
        ->andReturn((object) ['id' => 'prod_2']);

    $productResource->shouldReceive('archive')
        ->once()
        ->with('prod_3')
        ->andReturn((object) ['id' => 'prod_3']);

    $service = new ProductService($client);
    $archivedCount = $service->archiveRemoved(['product_1']);

    expect($archivedCount)->toBe(2)
        ->and(StripeProduct::where('active', true)->count())->toBe(1)
        ->and(StripeProduct::where('active', false)->count())->toBe(2);
});

it('does not archive products that are in configured list', function () {
    StripeProduct::create([
        'key' => 'product_1',
        'stripe_id' => 'prod_1',
        'name' => 'Product 1',
        'active' => true,
    ]);

    StripeProduct::create([
        'key' => 'product_2',
        'stripe_id' => 'prod_2',
        'name' => 'Product 2',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldNotReceive('archive');

    $service = new ProductService($client);
    $archivedCount = $service->archiveRemoved(['product_1', 'product_2']);

    expect($archivedCount)->toBe(0)
        ->and(StripeProduct::where('active', true)->count())->toBe(2);
});

it('does not archive already inactive products', function () {
    StripeProduct::create([
        'key' => 'active_product',
        'stripe_id' => 'prod_active',
        'name' => 'Active Product',
        'active' => true,
    ]);

    StripeProduct::create([
        'key' => 'inactive_product',
        'stripe_id' => 'prod_inactive',
        'name' => 'Inactive Product',
        'active' => false,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldNotReceive('archive');

    $service = new ProductService($client);
    $archivedCount = $service->archiveRemoved(['active_product']);

    expect($archivedCount)->toBe(0);
});
