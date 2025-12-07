<?php

use Mockery as m;
use ValentinMorice\LaravelStripeRepository\Actions\Deployer\Price\CreateAction;
use ValentinMorice\LaravelStripeRepository\Contracts\PriceResourceInterface;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripePrice;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
});

afterEach(function () {
    m::close();
});

it('creates one-time price in Stripe and database', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null, null)
        ->andReturn('price_456');

    $action = new CreateAction($client);
    $definition = new PriceDefinition(amount: 1000);

    $result = $action->handle($product, 'default', $definition);

    expect($result)->toBeInstanceOf(StripePrice::class)
        ->and($result->product_id)->toBe($product->id)
        ->and($result->type)->toBe('default')
        ->and($result->stripe_id)->toBe('price_456')
        ->and($result->amount)->toBe(1000)
        ->and($result->currency)->toBe('eur')
        ->and($result->recurring)->toBeNull()
        ->and($result->nickname)->toBeNull()
        ->and($result->active)->toBeTrue()
        ->and(StripePrice::count())->toBe(1);
});

it('creates recurring price with interval', function () {
    $product = StripeProduct::create([
        'key' => 'subscription',
        'stripe_id' => 'prod_sub',
        'name' => 'Subscription',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_sub', 999, 'eur', ['interval' => 'month'], null)
        ->andReturn('price_monthly');

    $action = new CreateAction($client);
    $definition = new PriceDefinition(
        amount: 999,
        recurring: ['interval' => 'month']
    );

    $result = $action->handle($product, 'monthly', $definition);

    expect($result->recurring)->toBe(['interval' => 'month'])
        ->and($result->type)->toBe('monthly')
        ->and($result->amount)->toBe(999);
});

it('creates price with custom currency', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 2000, 'usd', null, null)
        ->andReturn('price_usd');

    $action = new CreateAction($client);
    $definition = new PriceDefinition(amount: 2000, currency: 'usd');

    $result = $action->handle($product, 'default', $definition);

    expect($result->currency)->toBe('usd')
        ->and($result->amount)->toBe(2000);
});

it('creates price with nickname', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null, 'Monthly Plan')
        ->andReturn('price_456');

    $action = new CreateAction($client);
    $definition = new PriceDefinition(amount: 1000, nickname: 'Monthly Plan');

    $result = $action->handle($product, 'default', $definition);

    expect($result->nickname)->toBe('Monthly Plan');
});

it('creates price with all optional fields', function () {
    $product = StripeProduct::create([
        'key' => 'subscription',
        'stripe_id' => 'prod_sub',
        'name' => 'Subscription',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_sub', 1999, 'usd', ['interval' => 'year'], 'Annual Plan')
        ->andReturn('price_annual');

    $action = new CreateAction($client);
    $definition = new PriceDefinition(
        amount: 1999,
        currency: 'usd',
        recurring: ['interval' => 'year'],
        nickname: 'Annual Plan'
    );

    $result = $action->handle($product, 'yearly', $definition);

    expect($result->type)->toBe('yearly')
        ->and($result->amount)->toBe(1999)
        ->and($result->currency)->toBe('usd')
        ->and($result->recurring)->toBe(['interval' => 'year'])
        ->and($result->nickname)->toBe('Annual Plan');
});

it('handles multiple price types for same product', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->twice()
        ->withArgs(fn ($productId, $amount, $currency, $recurring, $nickname) => true)
        ->andReturn('price_default', 'price_premium');

    $action = new CreateAction($client);

    $defaultResult = $action->handle($product, 'default', new PriceDefinition(1000));
    $premiumResult = $action->handle($product, 'premium', new PriceDefinition(2000));

    expect($defaultResult->type)->toBe('default')
        ->and($premiumResult->type)->toBe('premium')
        ->and(StripePrice::count())->toBe(2);
});

it('throws exception when Stripe API fails', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->andThrow(new \Exception('Stripe API error'));

    $action = new CreateAction($client);
    $definition = new PriceDefinition(amount: 1000);

    expect(fn () => $action->handle($product, 'default', $definition))
        ->toThrow(\Exception::class, 'Stripe API error');

    // Verify no database record was created
    expect(StripePrice::count())->toBe(0);
});
