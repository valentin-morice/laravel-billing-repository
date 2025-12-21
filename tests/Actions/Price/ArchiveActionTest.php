<?php

use Mockery as m;
use ValentinMorice\LaravelStripeRepository\Actions\Price\ArchiveAction;
use ValentinMorice\LaravelStripeRepository\Contracts\PriceResourceInterface;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\Models\StripePrice;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
});

afterEach(function () {
    m::close();
});

it('archives price in Stripe and database', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $price = StripePrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'stripe_id' => 'price_456',
        'amount' => 1000,
        'currency' => 'eur',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_456')
        ->andReturn((object) ['id' => 'price_456', 'active' => false]);

    $action = new ArchiveAction($client);
    $result = $action->handle($price);

    expect($result)->toBeInstanceOf(StripePrice::class)
        ->and($result->active)->toBeFalse()
        ->and($result->stripe_id)->toBe('price_456')
        ->and(StripePrice::where('active', false)->count())->toBe(1);
});

it('preserves other price attributes when archiving', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $price = StripePrice::create([
        'product_id' => $product->id,
        'type' => 'monthly',
        'stripe_id' => 'price_monthly',
        'amount' => 999,
        'currency' => 'usd',
        'recurring' => ['interval' => 'month'],
        'nickname' => 'Monthly Plan',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
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
    $product = StripeProduct::create([
        'key' => 'subscription',
        'stripe_id' => 'prod_sub',
        'name' => 'Subscription',
        'active' => true,
    ]);

    $price = StripePrice::create([
        'product_id' => $product->id,
        'type' => 'yearly',
        'stripe_id' => 'price_yearly',
        'amount' => 10000,
        'currency' => 'eur',
        'recurring' => ['interval' => 'year'],
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
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
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $price = StripePrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'stripe_id' => 'price_456',
        'amount' => 1000,
        'currency' => 'eur',
        'nickname' => 'Special Price',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
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

it('throws exception when Stripe API fails', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $price = StripePrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'stripe_id' => 'price_456',
        'amount' => 1000,
        'currency' => 'eur',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
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
