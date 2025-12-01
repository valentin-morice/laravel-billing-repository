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
        ->with('prod_123', 1000, 'eur', null)
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

    expect($results['products_created'])->toBe(1)
        ->and($results['prices_created'])->toBe(1)
        ->and(StripeProduct::count())->toBe(1)
        ->and(StripePrice::count())->toBe(1);
});

it('iterates through multiple products and prices correctly', function () {
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

it('handles empty configuration gracefully', function () {
    [$client, $productResource, $priceResource] = mockStripeClient();

    $productResource->shouldNotReceive('create');
    $priceResource->shouldNotReceive('create');

    config(['stripe-repository.products' => []]);

    $deployer = new StripeDeployer($client);
    $results = $deployer->deploy();

    expect($results['products_created'])->toBe(0)
        ->and($results['prices_created'])->toBe(0);
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
