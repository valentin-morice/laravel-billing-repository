<?php

use Mockery as m;
use ValentinMorice\LaravelStripeRepository\Contracts\PriceResourceInterface;
use ValentinMorice\LaravelStripeRepository\Contracts\ProductResourceInterface;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\ProductDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripePrice;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;
use ValentinMorice\LaravelStripeRepository\StripeDeployer;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
});

afterEach(function () {
    m::close();
});

it('creates new product and price in Stripe and database', function () {
    // Mock Stripe client
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldReceive('create')
        ->once()
        ->with('Test Product', null)
        ->andReturn('prod_123');

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null)
        ->andReturn('price_456');

    // Set up config
    config(['stripe-repository.products' => [
        'test_product' => new ProductDefinition(
            name: 'Test Product',
            prices: [
                'default' => new PriceDefinition(1000),
            ],
        ),
    ]]);

    // Deploy
    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    // Assert results
    expect($results['products_created'])->toBe(1)
        ->and($results['prices_created'])->toBe(1)
        ->and(StripeProduct::count())->toBe(1)
        ->and(StripePrice::count())->toBe(1);

    // Assert database

    $product = StripeProduct::first();
    expect($product->key)->toBe('test_product')
        ->and($product->stripe_id)->toBe('prod_123')
        ->and($product->name)->toBe('Test Product')
        ->and($product->active)->toBeTrue();

    $price = StripePrice::first();
    expect($price->product_id)->toBe($product->id)
        ->and($price->type)->toBe('default')
        ->and($price->stripe_id)->toBe('price_456')
        ->and($price->amount)->toBe(1000)
        ->and($price->currency)->toBe('eur')
        ->and($price->active)->toBeTrue();
});

it('skips existing product and uses it', function () {
    // Create existing product in database
    $existingProduct = StripeProduct::create([
        'key' => 'existing_product',
        'stripe_id' => 'prod_existing',
        'name' => 'Existing Product',
        'active' => true,
    ]);

    // Mock Stripe client
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldReceive('retrieve')
        ->once()
        ->with('prod_existing')
        ->andReturn((object) ['id' => 'prod_existing']);

    $productResource->shouldNotReceive('create');

    $priceResource->shouldReceive('create')
        ->once()
        ->andReturn('price_new');

    // Set up config
    config(['stripe-repository.products' => [
        'existing_product' => new ProductDefinition(
            name: 'Existing Product',
            prices: [
                'default' => new PriceDefinition(2000),
            ],
        ),
    ]]);

    // Deploy
    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    // Assert no new products created
    expect($results['products_created'])->toBe(0)
        ->and($results['prices_created'])->toBe(1)
        ->and(StripeProduct::count())->toBe(1);
});

it('skips existing price with same amount', function () {
    // Create existing product and price
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    StripePrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'stripe_id' => 'price_existing',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => null,
        'active' => true,
    ]);

    // Mock Stripe client
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldReceive('retrieve')
        ->once()
        ->andReturn((object) ['id' => 'prod_123']);

    $priceResource->shouldNotReceive('create');

    // Set up config with same price
    config(['stripe-repository.products' => [
        'test_product' => new ProductDefinition(
            name: 'Test Product',
            prices: [
                'default' => new PriceDefinition(1000),
            ],
        ),
    ]]);

    // Deploy
    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    // Assert no new prices created
    expect($results['products_created'])->toBe(0)
        ->and($results['prices_created'])->toBe(0)
        ->and(StripePrice::count())->toBe(1);
});

it('creates recurring price with interval', function () {
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldReceive('create')
        ->once()
        ->andReturn('prod_123');

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 999, 'eur', ['interval' => 'month'])
        ->andReturn('price_monthly');

    config(['stripe-repository.products' => [
        'subscription' => new ProductDefinition(
            name: 'Subscription',
            prices: [
                'monthly' => new PriceDefinition(
                    amount: 999,
                    recurring: ['interval' => 'month']
                ),
            ],
        ),
    ]]);

    $deployer = new StripeDeployer($client);
    $deployer->deploy();

    $price = StripePrice::first();
    expect($price->recurring)->toBe(['interval' => 'month']);
});

it('creates multiple products and prices', function () {
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldReceive('create')->twice()->andReturn('prod_1', 'prod_2');
    $priceResource->shouldReceive('create')->times(3)->andReturn('price_1', 'price_2', 'price_3');

    config(['stripe-repository.products' => [
        'product_1' => new ProductDefinition(
            name: 'Product 1',
            prices: [
                'default' => new PriceDefinition(1000),
                'premium' => new PriceDefinition(2000),
            ],
        ),
        'product_2' => new ProductDefinition(
            name: 'Product 2',
            prices: [
                'default' => new PriceDefinition(3000),
            ],
        ),
    ]]);

    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    expect($results['products_created'])->toBe(2)
        ->and($results['prices_created'])->toBe(3)
        ->and(StripeProduct::count())->toBe(2)
        ->and(StripePrice::count())->toBe(3);
});

it('handles product with description', function () {
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldReceive('create')
        ->once()
        ->with('Product', 'Product description')
        ->andReturn('prod_123');

    $priceResource->shouldReceive('create')
        ->once()
        ->andReturn('price_123');

    config(['stripe-repository.products' => [
        'product' => new ProductDefinition(
            name: 'Product',
            prices: [
                'default' => new PriceDefinition(1000),
            ],
            description: 'Product description',
        ),
    ]]);

    $deployer = new StripeDeployer($client);
    $deployer->deploy();

    expect(StripeProduct::first()->name)->toBe('Product');
});

it('handles different currencies', function () {
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldReceive('create')
        ->once()
        ->andReturn('prod_123');

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'usd', null)
        ->andReturn('price_123');

    config(['stripe-repository.products' => [
        'product' => new ProductDefinition(
            name: 'Product',
            prices: [
                'default' => new PriceDefinition(
                    amount: 1000,
                    currency: 'usd'
                ),
            ],
        ),
    ]]);

    $deployer = new StripeDeployer($client);
    $deployer->deploy();

    $price = StripePrice::first();
    expect($price->currency)->toBe('usd');
});

it('returns zero counts when no changes needed', function () {
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldNotReceive('create');
    $priceResource->shouldNotReceive('create');

    config(['stripe-repository.products' => []]);

    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    expect($results['products_created'])->toBe(0);
    expect($results['prices_created'])->toBe(0);
});

// Helper function to mock Stripe client
function mockStripeClient(): array
{
    $productResource = m::mock(ProductResourceInterface::class);
    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);

    $client->shouldReceive('product')->andReturn($productResource);
    $client->shouldReceive('price')->andReturn($priceResource);

    return [$client, $productResource, $priceResource];
}
