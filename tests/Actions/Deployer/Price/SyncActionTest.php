<?php

use Mockery as m;
use ValentinMorice\LaravelStripeRepository\Actions\Deployer\Price\SyncAction;
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

it('creates new price when it does not exist', function () {
    // Create product
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    // Mock Stripe client
    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null)
        ->andReturn('price_456');

    // Execute action
    $action = new SyncAction($client);
    $definition = new PriceDefinition(amount: 1000);

    $price = $action->handle($product, 'default', $definition);

    // Assert price was created
    expect($price)->toBeInstanceOf(StripePrice::class)
        ->and($price->product_id)->toBe($product->id)
        ->and($price->type)->toBe('default')
        ->and($price->stripe_id)->toBe('price_456')
        ->and($price->amount)->toBe(1000)
        ->and($price->currency)->toBe('eur')
        ->and($price->recurring)->toBeNull()
        ->and($price->active)->toBeTrue();

    // Assert database
    expect(StripePrice::count())->toBe(1);
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
        ->with('prod_123', 2000, 'usd', null)
        ->andReturn('price_789');

    $action = new SyncAction($client);
    $definition = new PriceDefinition(amount: 2000, currency: 'usd');

    $price = $action->handle($product, 'default', $definition);

    expect($price->currency)->toBe('usd');
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
        ->with('prod_sub', 999, 'eur', ['interval' => 'month'])
        ->andReturn('price_monthly');

    $action = new SyncAction($client);
    $definition = new PriceDefinition(
        amount: 999,
        recurring: ['interval' => 'month']
    );

    $price = $action->handle($product, 'monthly', $definition);

    expect($price->recurring)->toBe(['interval' => 'month'])
        ->and($price->type)->toBe('monthly');
});

it('returns null when price with same amount already exists', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    // Create existing price
    StripePrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'stripe_id' => 'price_existing',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => null,
        'active' => true,
    ]);

    // Mock Stripe client
    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldNotReceive('create');

    // Execute action
    $action = new SyncAction($client);
    $definition = new PriceDefinition(amount: 1000);

    $price = $action->handle($product, 'default', $definition);

    // Assert null was returned (skipped)
    expect($price)->toBeNull()
        ->and(StripePrice::count())->toBe(1);
});

it('creates new price when amount differs from existing', function () {
    $product = StripeProduct::create([
        'key' => 'test_product',
        'stripe_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    // Create existing price with different amount
    StripePrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'stripe_id' => 'price_old',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => null,
        'active' => true,
    ]);

    // Mock Stripe client
    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(StripeClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 2000, 'eur', null)
        ->andReturn('price_new');

    // Execute action with different amount
    $action = new SyncAction($client);
    $definition = new PriceDefinition(amount: 2000);

    $price = $action->handle($product, 'default', $definition);

    // Assert new price was created
    expect($price)->not->toBeNull()
        ->and($price->stripe_id)->toBe('price_new')
        ->and($price->amount)->toBe(2000)
        ->and(StripePrice::count())->toBe(2);
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
        ->andReturn('price_default', 'price_premium');

    $action = new SyncAction($client);

    // Create default price
    $defaultPrice = $action->handle($product, 'default', new PriceDefinition(1000));

    // Create premium price
    $premiumPrice = $action->handle($product, 'premium', new PriceDefinition(2000));

    expect($defaultPrice->type)->toBe('default')
        ->and($premiumPrice->type)->toBe('premium')
        ->and(StripePrice::count())->toBe(2);
});
