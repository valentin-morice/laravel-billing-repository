<?php

use Mockery as m;
use ValentinMorice\LaravelBillingRepository\EnumGenerator\EnumGeneratorService;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\ProductResourceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\BuildChangeSetAction;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;
use ValentinMorice\LaravelBillingRepository\Deployer\DeployerService;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\PriceService;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\ProductService;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);

    // Mock enum generator to prevent writing to real enum files during tests
    app()->instance(
        EnumGeneratorService::class,
        m::mock(EnumGeneratorService::class)->shouldReceive('generate')->andReturn(true)->getMock()
    );
});

afterEach(function () {
    m::close();
});

it('orchestrates product and price creation end-to-end', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);
    $client->shouldReceive('price')->andReturn($priceResource);

    $detectChanges = new DetectChangesAction;
    $productService = new ProductService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);
    $priceService = new PriceService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);

    // Bind services to container
    app()->instance(ProductServiceInterface::class, $productService);
    app()->instance(PriceServiceInterface::class, $priceService);

    $buildChangeSet = app(BuildChangeSetAction::class);
    $deployer = new DeployerService($buildChangeSet);

    $productResource->shouldReceive('create')
        ->once()
        ->with('Test Product', null, null, null, null)
        ->andReturn('prod_123');

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null, null, null, null, null, null)
        ->andReturn('price_456');

    config(['billing.products' => [
        'test_product' => [
            'name' => 'Test Product',
            'prices' => [
                'default' => [
                    'amount' => 1000,
                    'currency' => 'eur',
                ],
            ],
        ],
    ]]);

    $changeSet = $deployer->deploy();
    $summary = $changeSet->getSummary();

    expect($summary['products']['created'])->toBe(1)
        ->and($summary['products']['updated'])->toBe(0)
        ->and($summary['products']['unchanged'])->toBe(0)
        ->and($summary['products']['archived'])->toBe(0)
        ->and($summary['prices']['created'])->toBe(1)
        ->and($summary['prices']['updated'])->toBe(0)
        ->and($summary['prices']['unchanged'])->toBe(0)
        ->and($summary['prices']['archived'])->toBe(0)
        ->and(BillingProduct::count())->toBe(1)
        ->and(BillingPrice::count())->toBe(1);
});

it('iterates through multiple products and prices correctly', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);
    $client->shouldReceive('price')->andReturn($priceResource);

    $detectChanges = new DetectChangesAction;
    $productService = new ProductService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);
    $priceService = new PriceService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);

    // Bind services to container
    app()->instance(ProductServiceInterface::class, $productService);
    app()->instance(PriceServiceInterface::class, $priceService);

    $buildChangeSet = app(BuildChangeSetAction::class);
    $deployer = new DeployerService($buildChangeSet);

    $productResource->shouldReceive('create')
        ->twice()
        ->withArgs(fn ($name, $description) => true)
        ->andReturn('prod_1', 'prod_2');
    $priceResource->shouldReceive('create')
        ->times(3)
        ->withArgs(fn ($productId, $amount, $currency, $recurring, $nickname) => true)
        ->andReturn('price_1', 'price_2', 'price_3');

    config(['billing.products' => [
        'product_1' => [
            'name' => 'Product 1',
            'prices' => [
                'default' => [
                    'amount' => 1000,
                    'currency' => 'eur',
                ],
                'premium' => [
                    'amount' => 2000,
                    'currency' => 'eur',
                ],
            ],
        ],
        'product_2' => [
            'name' => 'Product 2',
            'prices' => [
                'default' => [
                    'amount' => 3000,
                    'currency' => 'eur',
                ],
            ],
        ],
    ]]);

    $changeSet = $deployer->deploy();
    $summary = $changeSet->getSummary();

    expect($summary['products']['created'])->toBe(2)
        ->and($summary['products']['updated'])->toBe(0)
        ->and($summary['products']['unchanged'])->toBe(0)
        ->and($summary['products']['archived'])->toBe(0)
        ->and($summary['prices']['created'])->toBe(3)
        ->and($summary['prices']['updated'])->toBe(0)
        ->and($summary['prices']['unchanged'])->toBe(0)
        ->and($summary['prices']['archived'])->toBe(0)
        ->and(BillingProduct::count())->toBe(2)
        ->and(BillingPrice::count())->toBe(3);
});

it('handles empty configuration gracefully', function () {
    $productResource = m::mock(ProductResourceInterface::class);
    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);
    $client->shouldReceive('price')->andReturn($priceResource);

    $detectChanges = new DetectChangesAction;
    $productService = new ProductService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);
    $priceService = new PriceService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);

    // Bind services to container
    app()->instance(ProductServiceInterface::class, $productService);
    app()->instance(PriceServiceInterface::class, $priceService);

    $buildChangeSet = app(BuildChangeSetAction::class);
    $deployer = new DeployerService($buildChangeSet);

    $productResource->shouldNotReceive('create');
    $priceResource->shouldNotReceive('create');

    config(['billing.products' => []]);

    $changeSet = $deployer->deploy();
    $summary = $changeSet->getSummary();

    expect($summary['products']['created'])->toBe(0)
        ->and($summary['products']['updated'])->toBe(0)
        ->and($summary['products']['unchanged'])->toBe(0)
        ->and($summary['products']['archived'])->toBe(0)
        ->and($summary['prices']['created'])->toBe(0)
        ->and($summary['prices']['updated'])->toBe(0)
        ->and($summary['prices']['unchanged'])->toBe(0)
        ->and($summary['prices']['archived'])->toBe(0);
});

it('archives prices that are removed from config', function () {
    // First create a product with two prices
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'monthly',
        'provider_id' => 'price_monthly',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => ['interval' => 'month'],
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'yearly',
        'provider_id' => 'price_yearly',
        'amount' => 10000,
        'currency' => 'eur',
        'recurring' => ['interval' => 'year'],
        'active' => true,
    ]);

    // Now configure product with only monthly price (yearly removed)
    $productResource = m::mock(ProductResourceInterface::class);
    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);
    $client->shouldReceive('price')->andReturn($priceResource);

    $detectChanges = new DetectChangesAction;
    $productService = new ProductService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);
    $priceService = new PriceService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);

    // Bind services to container
    app()->instance(ProductServiceInterface::class, $productService);
    app()->instance(PriceServiceInterface::class, $priceService);

    $buildChangeSet = app(BuildChangeSetAction::class);
    $deployer = new DeployerService($buildChangeSet);

    $productResource->shouldNotReceive('create');
    $productResource->shouldNotReceive('update');

    $priceResource->shouldNotReceive('create');

    // Expect yearly price to be archived
    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_yearly')
        ->andReturn((object) ['id' => 'price_yearly', 'active' => false]);

    config(['billing.products' => [
        'test_product' => [
            'name' => 'Test Product',
            'prices' => [
                'monthly' => [
                    'amount' => 1000,
                    'currency' => 'eur',
                    'recurring' => ['interval' => 'month'],
                ],
                // yearly removed
            ],
        ],
    ]]);

    $changeSet = $deployer->deploy();
    $summary = $changeSet->getSummary();

    expect($summary['products']['unchanged'])->toBe(1)
        ->and($summary['products']['archived'])->toBe(0)
        ->and($summary['prices']['unchanged'])->toBe(1) // monthly unchanged
        ->and($summary['prices']['archived'])->toBe(1) // yearly archived
        ->and(BillingPrice::count())->toBe(2)
        ->and(BillingPrice::where('active', true)->count())->toBe(1)
        ->and(BillingPrice::where('active', false)->count())->toBe(1);
});

it('archives products that are removed from config', function () {
    // Create two products
    BillingProduct::create([
        'key' => 'product_1',
        'provider_id' => 'prod_1',
        'name' => 'Product 1',
        'active' => true,
    ]);

    BillingProduct::create([
        'key' => 'product_2',
        'provider_id' => 'prod_2',
        'name' => 'Product 2',
        'active' => true,
    ]);

    // Configure only product_1 (product_2 removed)
    $productResource = m::mock(ProductResourceInterface::class);
    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);
    $client->shouldReceive('price')->andReturn($priceResource);

    $detectChanges = new DetectChangesAction;
    $productService = new ProductService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);
    $priceService = new PriceService($client, $detectChanges, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor);

    // Bind services to container
    app()->instance(ProductServiceInterface::class, $productService);
    app()->instance(PriceServiceInterface::class, $priceService);

    $buildChangeSet = app(BuildChangeSetAction::class);
    $deployer = new DeployerService($buildChangeSet);

    $productResource->shouldNotReceive('create');
    $productResource->shouldNotReceive('update');

    // Expect product_2 to be archived
    $productResource->shouldReceive('archive')
        ->once()
        ->with('prod_2')
        ->andReturn((object) ['id' => 'prod_2', 'active' => false]);

    config(['billing.products' => [
        'product_1' => [
            'name' => 'Product 1',
            'prices' => [],
        ],
        // product_2 removed
    ]]);

    $changeSet = $deployer->deploy();
    $summary = $changeSet->getSummary();

    expect($summary['products']['unchanged'])->toBe(1) // product_1 unchanged
        ->and($summary['products']['archived'])->toBe(1) // product_2 archived
        ->and(BillingProduct::count())->toBe(2)
        ->and(BillingProduct::where('active', true)->count())->toBe(1)
        ->and(BillingProduct::where('active', false)->count())->toBe(1);
});
