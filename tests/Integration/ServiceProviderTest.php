<?php

/** @noinspection PhpUnhandledExceptionInspection */

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderAdapterInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface as ServicesPriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface as ServicesProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\PriceService;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\ProductService;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeAdapter;

beforeEach(function () {
    config(['billing.provider' => 'stripe']);
});

it('resolves provider adapter interface from container', function () {
    $adapter = app(ProviderAdapterInterface::class);

    expect($adapter)->toBeInstanceOf(ProviderAdapterInterface::class)
        ->and($adapter)->toBeInstanceOf(StripeAdapter::class);
});

it('resolves provider client interface from container', function () {
    $client = app(ProviderClientInterface::class);

    expect($client)->toBeInstanceOf(ProviderClientInterface::class);
});

it('resolves product service interface from container', function () {
    $service = app(ServicesProductServiceInterface::class);

    expect($service)->toBeInstanceOf(ServicesProductServiceInterface::class)
        ->and($service)->toBeInstanceOf(ProductService::class);
});

it('resolves price service interface from container', function () {
    $service = app(ServicesPriceServiceInterface::class);

    expect($service)->toBeInstanceOf(ServicesPriceServiceInterface::class)
        ->and($service)->toBeInstanceOf(PriceService::class);
});

it('returns same provider adapter instance on multiple resolutions', function () {
    $adapter1 = app(ProviderAdapterInterface::class);
    $adapter2 = app(ProviderAdapterInterface::class);

    expect($adapter1)->toBe($adapter2);
});

it('returns same provider client instance on multiple resolutions', function () {
    $client1 = app(ProviderClientInterface::class);
    $client2 = app(ProviderClientInterface::class);

    expect($client1)->toBe($client2);
});

it('provider client is resolved from provider adapter', function () {
    $adapter = app(ProviderAdapterInterface::class);
    $clientFromAdapter = $adapter->client();
    $clientFromContainer = app(ProviderClientInterface::class);

    expect($clientFromAdapter)->toBe($clientFromContainer);
});

it('services receive the same client instance', function () {
    $productService = app(ServicesProductServiceInterface::class);
    $priceService = app(ServicesPriceServiceInterface::class);
    $client = app(ProviderClientInterface::class);

    // Use reflection to access protected client property
    $productReflection = new ReflectionClass($productService);
    $productClientProperty = $productReflection->getProperty('client');
    $productClient = $productClientProperty->getValue($productService);

    $priceReflection = new ReflectionClass($priceService);
    $priceClientProperty = $priceReflection->getProperty('client');
    $priceClient = $priceClientProperty->getValue($priceService);

    expect($productClient)->toBe($client)
        ->and($priceClient)->toBe($client);
});
