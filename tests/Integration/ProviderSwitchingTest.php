<?php

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderAdapterInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Exceptions\IO\ConfigurationException;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeAdapter;

it('throws exception when provider is not configured', function () {
    config(['billing.provider' => null]);

    app()->forgetInstance(ProviderAdapterInterface::class);

    expect(fn () => app(ProviderAdapterInterface::class))
        ->toThrow(ConfigurationException::class, 'Billing provider not configured');
});

it('uses stripe when explicitly configured', function () {
    config(['billing.provider' => 'stripe']);

    app()->forgetInstance(ProviderAdapterInterface::class);

    $adapter = app(ProviderAdapterInterface::class);

    expect($adapter)->toBeInstanceOf(StripeAdapter::class);
});

it('throws exception for unknown provider in adapter', function () {
    config(['billing.provider' => 'unknown_provider']);

    app()->forgetInstance(ProviderAdapterInterface::class);

    expect(fn () => app(ProviderAdapterInterface::class))
        ->toThrow(ConfigurationException::class, "Unknown billing provider: 'unknown_provider'");
});

it('throws exception for unknown provider in product service', function () {
    config(['billing.provider' => 'paddle']);

    app()->forgetInstance(ProductServiceInterface::class);

    expect(fn () => app(ProductServiceInterface::class))
        ->toThrow(ConfigurationException::class, "Unknown billing provider: 'paddle'");
});

it('throws exception for unknown provider in price service', function () {
    config(['billing.provider' => 'paypal']);

    app()->forgetInstance(PriceServiceInterface::class);

    expect(fn () => app(PriceServiceInterface::class))
        ->toThrow(ConfigurationException::class, "Unknown billing provider: 'paypal'");
});

it('can switch providers between tests', function () {
    // First, use stripe
    config(['billing.provider' => 'stripe']);
    app()->forgetInstance(ProviderAdapterInterface::class);

    $stripeAdapter = app(ProviderAdapterInterface::class);
    expect($stripeAdapter->name())->toBe('stripe');

    // Then try to switch to another provider (should fail since not implemented)
    config(['billing.provider' => 'paddle']);
    app()->forgetInstance(ProviderAdapterInterface::class);

    expect(fn () => app(ProviderAdapterInterface::class))
        ->toThrow(ConfigurationException::class);
});
