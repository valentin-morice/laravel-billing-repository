<?php

use ValentinMorice\LaravelBillingRepository\Deployer;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    config(['billing.provider' => 'stripe']);
});

it('can resolve deployer from container', function () {
    $deployer = app(Deployer::class);

    expect($deployer)->toBeInstanceOf(Deployer::class);
});

it('deployer receives correctly resolved services', function () {
    $deployer = app(Deployer::class);

    // Use reflection to verify the services are set
    $reflection = new ReflectionClass($deployer);

    $productServiceProperty = $reflection->getProperty('productService');
    $productService = $productServiceProperty->getValue($deployer);

    $priceServiceProperty = $reflection->getProperty('priceService');
    $priceService = $priceServiceProperty->getValue($deployer);

    expect($productService)->toBeInstanceOf(\ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface::class)
        ->and($priceService)->toBeInstanceOf(\ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface::class);
});

it('can call deploy method on resolved deployer', function () {
    config(['billing.products' => []]);

    $deployer = app(Deployer::class);
    $results = $deployer->deploy();

    expect($results)->toBeArray()
        ->and($results)->toHaveKeys(['products', 'prices'])
        ->and($results['products'])->toHaveKeys(['created', 'updated', 'unchanged', 'archived'])
        ->and($results['prices'])->toHaveKeys(['created', 'updated', 'unchanged', 'archived']);
});

it('deployer uses stripe services when provider is stripe', function () {
    config(['billing.provider' => 'stripe']);

    app()->forgetInstance(Deployer::class);
    app()->forgetInstance(\ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface::class);
    app()->forgetInstance(\ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface::class);

    $deployer = app(Deployer::class);

    $reflection = new ReflectionClass($deployer);
    $productServiceProperty = $reflection->getProperty('productService');
    $productService = $productServiceProperty->getValue($deployer);

    expect($productService)->toBeInstanceOf(\ValentinMorice\LaravelBillingRepository\Stripe\Services\ProductService::class);
});
