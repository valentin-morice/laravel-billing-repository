<?php

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderAdapterInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeAdapter;

it('stripe adapter implements provider adapter interface', function () {
    $adapter = new StripeAdapter;

    expect($adapter)->toBeInstanceOf(ProviderAdapterInterface::class);
});

it('stripe adapter returns stripe as provider name', function () {
    $adapter = new StripeAdapter;

    expect($adapter->name())->toBe('stripe');
});

it('stripe adapter returns a provider client', function () {
    $adapter = new StripeAdapter;
    $client = $adapter->client();

    expect($client)->toBeInstanceOf(ProviderClientInterface::class);
});

it('stripe adapter returns same client instance on multiple calls', function () {
    $adapter = new StripeAdapter;
    $client1 = $adapter->client();
    $client2 = $adapter->client();

    expect($client1)->toBe($client2);
});

it('provider client has product resource', function () {
    $adapter = new StripeAdapter;
    $client = $adapter->client();

    expect($client->product())->not->toBeNull()
        ->and($client->product())->toBeInstanceOf(\ValentinMorice\LaravelBillingRepository\Contracts\Resources\ProductResourceInterface::class);
});

it('provider client has price resource', function () {
    $adapter = new StripeAdapter;
    $client = $adapter->client();

    expect($client->price())->not->toBeNull()
        ->and($client->price())->toBeInstanceOf(\ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface::class);
});
