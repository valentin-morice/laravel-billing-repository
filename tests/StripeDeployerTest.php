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

it('orchestrates product and price creation end-to-end', function () {
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldReceive('create')
        ->once()
        ->with('Test Product', null)
        ->andReturn('prod_123');

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null, null)
        ->andReturn('price_456');

    config(['stripe-repository.products' => [
        'test_product' => new ProductDefinition(
            name: 'Test Product',
            prices: [
                'default' => new PriceDefinition(1000),
            ],
        ),
    ]]);

    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    expect($results['products']['created'])->toBe(1)
        ->and($results['products']['updated'])->toBe(0)
        ->and($results['products']['unchanged'])->toBe(0)
        ->and($results['products']['archived'])->toBe(0)
        ->and($results['prices']['created'])->toBe(1)
        ->and($results['prices']['updated'])->toBe(0)
        ->and($results['prices']['unchanged'])->toBe(0)
        ->and($results['prices']['archived'])->toBe(0)
        ->and(StripeProduct::count())->toBe(1)
        ->and(StripePrice::count())->toBe(1);
});

it('iterates through multiple products and prices correctly', function () {
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldReceive('create')
        ->twice()
        ->withArgs(fn ($name, $description) => true)
        ->andReturn('prod_1', 'prod_2');
    $priceResource->shouldReceive('create')
        ->times(3)
        ->withArgs(fn ($productId, $amount, $currency, $recurring, $nickname) => true)
        ->andReturn('price_1', 'price_2', 'price_3');

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

    expect($results['products']['created'])->toBe(2)
        ->and($results['products']['updated'])->toBe(0)
        ->and($results['products']['unchanged'])->toBe(0)
        ->and($results['products']['archived'])->toBe(0)
        ->and($results['prices']['created'])->toBe(3)
        ->and($results['prices']['updated'])->toBe(0)
        ->and($results['prices']['unchanged'])->toBe(0)
        ->and($results['prices']['archived'])->toBe(0)
        ->and(StripeProduct::count())->toBe(2)
        ->and(StripePrice::count())->toBe(3);
});

it('handles empty configuration gracefully', function () {
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldNotReceive('create');
    $priceResource->shouldNotReceive('create');

    config(['stripe-repository.products' => []]);

    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    expect($results['products']['created'])->toBe(0)
        ->and($results['products']['updated'])->toBe(0)
        ->and($results['products']['unchanged'])->toBe(0)
        ->and($results['products']['archived'])->toBe(0)
        ->and($results['prices']['created'])->toBe(0)
        ->and($results['prices']['updated'])->toBe(0)
        ->and($results['prices']['unchanged'])->toBe(0)
        ->and($results['prices']['archived'])->toBe(0);
});

it('archives prices that are removed from config', function () {
    // First create a product with two prices
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    StripePrice::create([
        'product_id' => $product->id,
        'type' => 'monthly',
        'stripe_id' => 'price_monthly',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => ['interval' => 'month'],
        'active' => true,
    ]);

    StripePrice::create([
        'product_id' => $product->id,
        'type' => 'yearly',
        'stripe_id' => 'price_yearly',
        'amount' => 10000,
        'currency' => 'eur',
        'recurring' => ['interval' => 'year'],
        'active' => true,
    ]);

    // Now configure product with only monthly price (yearly removed)
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldNotReceive('create');
    $productResource->shouldNotReceive('update');

    $priceResource->shouldNotReceive('create');

    // Expect yearly price to be archived
    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_yearly')
        ->andReturn((object) ['id' => 'price_yearly', 'active' => false]);

    config(['stripe-repository.products' => [
        'test_product' => new ProductDefinition(
            name: 'Test Product',
            prices: [
                'monthly' => new PriceDefinition(1000, recurring: ['interval' => 'month']),
                // yearly removed
            ],
        ),
    ]]);

    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    expect($results['products']['unchanged'])->toBe(1)
        ->and($results['products']['archived'])->toBe(0)
        ->and($results['prices']['unchanged'])->toBe(1) // monthly unchanged
        ->and($results['prices']['archived'])->toBe(1) // yearly archived
        ->and(StripePrice::count())->toBe(2)
        ->and(StripePrice::where('active', true)->count())->toBe(1)
        ->and(StripePrice::where('active', false)->count())->toBe(1);
});

it('archives products that are removed from config', function () {
    // Create two products
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

    // Configure only product_1 (product_2 removed)
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldNotReceive('create');
    $productResource->shouldNotReceive('update');

    // Expect product_2 to be archived
    $productResource->shouldReceive('archive')
        ->once()
        ->with('prod_2')
        ->andReturn((object) ['id' => 'prod_2', 'active' => false]);

    config(['stripe-repository.products' => [
        'product_1' => new ProductDefinition(
            name: 'Product 1',
            prices: [],
        ),
        // product_2 removed
    ]]);

    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    expect($results['products']['unchanged'])->toBe(1) // product_1 unchanged
        ->and($results['products']['archived'])->toBe(1) // product_2 archived
        ->and(StripeProduct::count())->toBe(2)
        ->and(StripeProduct::where('active', true)->count())->toBe(1)
        ->and(StripeProduct::where('active', false)->count())->toBe(1);
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
