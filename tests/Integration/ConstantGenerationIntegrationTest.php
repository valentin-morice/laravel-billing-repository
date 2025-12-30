<?php

use ValentinMorice\LaravelBillingRepository\ConstantGenerator\ConstantGeneratorService;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Deployer\DeployerService;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\PostDeploy\GenerateConstantsStage;
use ValentinMorice\LaravelBillingRepository\Facades\BillingRepository;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

it('constant generator service can be resolved from container', function () {
    $service = app(ConstantGeneratorService::class);

    expect($service)->toBeInstanceOf(ConstantGeneratorService::class);
});

it('generate constants stage can be resolved from container', function () {
    $stage = app(GenerateConstantsStage::class);

    expect($stage)->toBeInstanceOf(GenerateConstantsStage::class);
});

it('can use BillingRepository facade with manually created data', function () {
    // Manually create test data in database
    $product = BillingProduct::create([
        'key' => 'nif',
        'provider_id' => 'prod_123',
        'name' => 'NIF Portugal',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_456',
        'amount' => 12000,
        'currency' => 'eur',
        'active' => true,
    ]);

    // Use BillingRepository facade
    $productId = BillingRepository::productId('nif');
    $priceId = BillingRepository::priceId('nif', 'default');
    $retrievedProduct = BillingRepository::product('nif');
    $prices = BillingRepository::prices('nif');

    expect($productId)->toBe('prod_123')
        ->and($priceId)->toBe('price_456')
        ->and($retrievedProduct)->toBeInstanceOf(BillingProduct::class)
        ->and($retrievedProduct->key)->toBe('nif')
        ->and($prices)->toHaveCount(1)
        ->and($prices->first()->type)->toBe('default');
});

it('dry-run does not call constant generator', function () {
    // Set up config
    config(['billing.products' => [
        'test' => new ProductDefinition(
            name: 'Test',
            prices: [
                'default' => new PriceDefinition(1000),
            ],
        ),
    ]]);

    // Spy on ConstantGeneratorService
    $generatorSpy = $this->spy(ConstantGeneratorService::class);

    // Run analyze (dry-run)
    $deployer = app(DeployerService::class);
    $changeSet = $deployer->analyze();

    // Verify constant generator was NOT called
    $generatorSpy->shouldNotHaveReceived('generateAll');

    // Verify no database changes
    expect(BillingProduct::count())->toBe(0)
        ->and(BillingPrice::count())->toBe(0);
});

it('billing repository facade works correctly with active scope', function () {
    // Create active and inactive products
    BillingProduct::create([
        'key' => 'active_product',
        'provider_id' => 'prod_active',
        'name' => 'Active Product',
        'active' => true,
    ]);

    BillingProduct::create([
        'key' => 'inactive_product',
        'provider_id' => 'prod_inactive',
        'name' => 'Inactive Product',
        'active' => false,
    ]);

    // Verify facade only returns active product
    $productId = BillingRepository::productId('active_product');
    expect($productId)->toBe('prod_active');

    // Verify inactive product throws exception
    expect(fn () => BillingRepository::productId('inactive_product'))
        ->toThrow(\InvalidArgumentException::class);
});
