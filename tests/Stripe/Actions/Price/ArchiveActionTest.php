<?php

use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price\ArchiveAction;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
});

afterEach(function () {
    m::close();
});

it('archives price and stores in database', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $price = BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_456',
        'amount' => 1000,
        'currency' => 'eur',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_456')
        ->andReturn((object) ['id' => 'price_456', 'active' => false]);

    $action = new ArchiveAction($client);
    $result = $action->handle($price);

    expect($result)->toBeInstanceOf(BillingPrice::class)
        ->and($result->active)->toBeFalse()
        ->and($result->provider_id)->toBe('price_456')
        ->and(BillingPrice::where('active', false)->count())->toBe(1);
});

it('preserves other price attributes when archiving', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $price = BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'monthly',
        'provider_id' => 'price_monthly',
        'amount' => 999,
        'currency' => 'usd',
        'recurring' => ['interval' => 'month'],
        'nickname' => 'Monthly Plan',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_monthly')
        ->andReturn((object) ['id' => 'price_monthly', 'active' => false]);

    $action = new ArchiveAction($client);
    $result = $action->handle($price);

    expect($result->type)->toBe('monthly')
        ->and($result->amount)->toBe(999)
        ->and($result->currency)->toBe('usd')
        ->and($result->recurring)->toBe(['interval' => 'month'])
        ->and($result->nickname)->toBe('Monthly Plan')
        ->and($result->active)->toBeFalse();
});

it('archives recurring price correctly', function () {
    $product = BillingProduct::create([
        'key' => 'subscription',
        'provider_id' => 'prod_sub',
        'name' => 'Subscription',
        'active' => true,
    ]);

    $price = BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'yearly',
        'provider_id' => 'price_yearly',
        'amount' => 10000,
        'currency' => 'eur',
        'recurring' => ['interval' => 'year'],
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_yearly')
        ->andReturn((object) ['id' => 'price_yearly', 'active' => false]);

    $action = new ArchiveAction($client);
    $result = $action->handle($price);

    expect($result->recurring)->toBe(['interval' => 'year'])
        ->and($result->active)->toBeFalse();
});

it('archives price with nickname correctly', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $price = BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_456',
        'amount' => 1000,
        'currency' => 'eur',
        'nickname' => 'Special Price',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_456')
        ->andReturn((object) ['id' => 'price_456', 'active' => false]);

    $action = new ArchiveAction($client);
    $result = $action->handle($price);

    expect($result->nickname)->toBe('Special Price')
        ->and($result->active)->toBeFalse();
});

it('throws exception when provider API fails', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $price = BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_456',
        'amount' => 1000,
        'currency' => 'eur',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->andThrow(new \Exception('Stripe API error'));

    $action = new ArchiveAction($client);

    expect(fn () => $action->handle($price))
        ->toThrow(\Exception::class, 'Stripe API error');

    // Verify database was not updated
    $price->refresh();
    expect($price->active)->toBeTrue();
});
