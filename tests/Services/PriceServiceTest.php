<?php

use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Contracts\PriceResourceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\PriceService;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
});

afterEach(function () {
    m::close();
});

it('creates new price when it does not exist', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null, null)
        ->andReturn('price_456');

    $service = new PriceService($client);
    $definition = new PriceDefinition(amount: 1000);

    $result = $service->sync($product, 'default', $definition);

    expect($result)->toBeArray()
        ->and($result['action'])->toBe('created')
        ->and($result['price'])->toBeInstanceOf(BillingPrice::class)
        ->and($result['price']->amount)->toBe(1000)
        ->and($result['price']->currency)->toBe('eur')
        ->and(BillingPrice::count())->toBe(1);
});

it('creates recurring price with interval', function () {
    $product = BillingProduct::create([
        'key' => 'subscription',
        'provider_id' => 'prod_sub',
        'name' => 'Subscription',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_sub', 999, 'eur', ['interval' => 'month'], null)
        ->andReturn('price_monthly');

    $service = new PriceService($client);
    $definition = new PriceDefinition(
        amount: 999,
        recurring: ['interval' => 'month']
    );

    $result = $service->sync($product, 'monthly', $definition);

    expect($result['action'])->toBe('created')
        ->and($result['price']->recurring)->toBe(['interval' => 'month'])
        ->and($result['price']->type)->toBe('monthly');
});

it('returns unchanged when price already exists with same data', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_existing',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => null,
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldNotReceive('create');
    $priceResource->shouldNotReceive('archive');

    $service = new PriceService($client);
    $definition = new PriceDefinition(amount: 1000);

    $result = $service->sync($product, 'default', $definition);

    expect($result['action'])->toBe('unchanged')
        ->and(BillingPrice::count())->toBe(1);
});

it('archives old price and creates new when amount changes', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_old',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => null,
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_old')
        ->andReturn((object) ['id' => 'price_old', 'active' => false]);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 2000, 'eur', null, null)
        ->andReturn('price_new');

    $service = new PriceService($client);
    $definition = new PriceDefinition(amount: 2000);

    $result = $service->sync($product, 'default', $definition);

    expect($result['action'])->toBe('updated')
        ->and($result['old'])->toBeInstanceOf(BillingPrice::class)
        ->and($result['new'])->toBeInstanceOf(BillingPrice::class)
        ->and($result['old']->active)->toBeFalse()
        ->and($result['new']->active)->toBeTrue()
        ->and($result['new']->amount)->toBe(2000)
        ->and(BillingPrice::count())->toBe(2)
        ->and(BillingPrice::where('active', true)->count())->toBe(1);
});

it('archives and recreates when currency changes', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_old',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => null,
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_old')
        ->andReturn((object) ['id' => 'price_old']);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'usd', null, null)
        ->andReturn('price_new');

    $service = new PriceService($client);
    $definition = new PriceDefinition(amount: 1000, currency: 'usd');

    $result = $service->sync($product, 'default', $definition);

    expect($result['action'])->toBe('updated')
        ->and($result['new']->currency)->toBe('usd');
});

it('archives and recreates when recurring interval changes', function () {
    $product = BillingProduct::create([
        'key' => 'subscription',
        'provider_id' => 'prod_sub',
        'name' => 'Subscription',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'subscription',
        'provider_id' => 'price_old',
        'amount' => 999,
        'currency' => 'eur',
        'recurring' => ['interval' => 'month'],
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_old')
        ->andReturn((object) ['id' => 'price_old']);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_sub', 999, 'eur', ['interval' => 'year'], null)
        ->andReturn('price_new');

    $service = new PriceService($client);
    $definition = new PriceDefinition(
        amount: 999,
        recurring: ['interval' => 'year']
    );

    $result = $service->sync($product, 'subscription', $definition);

    expect($result['action'])->toBe('updated')
        ->and($result['new']->recurring)->toBe(['interval' => 'year']);
});

it('handles multiple price types for same product', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->twice()
        ->withArgs(fn ($productId, $amount, $currency, $recurring, $nickname) => true)
        ->andReturn('price_default', 'price_premium');

    $service = new PriceService($client);

    $defaultResult = $service->sync($product, 'default', new PriceDefinition(1000));
    $premiumResult = $service->sync($product, 'premium', new PriceDefinition(2000));

    expect($defaultResult['price']->type)->toBe('default')
        ->and($premiumResult['price']->type)->toBe('premium')
        ->and(BillingPrice::count())->toBe(2);
});

it('archives removed prices not in configured list', function () {
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
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'yearly',
        'provider_id' => 'price_yearly',
        'amount' => 10000,
        'currency' => 'eur',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_yearly')
        ->andReturn((object) ['id' => 'price_yearly']);

    $service = new PriceService($client);
    $archivedCount = $service->archiveRemoved($product, ['monthly']);

    expect($archivedCount)->toBe(1)
        ->and(BillingPrice::where('active', true)->count())->toBe(1)
        ->and(BillingPrice::where('active', false)->count())->toBe(1);
});

it('does not archive prices that are in configured list', function () {
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
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldNotReceive('archive');

    $service = new PriceService($client);
    $archivedCount = $service->archiveRemoved($product, ['monthly']);

    expect($archivedCount)->toBe(0)
        ->and(BillingPrice::where('active', true)->count())->toBe(1);
});

it('creates price with nickname', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null, 'Monthly Plan')
        ->andReturn('price_456');

    $service = new PriceService($client);
    $definition = new PriceDefinition(amount: 1000, nickname: 'Monthly Plan');

    $result = $service->sync($product, 'default', $definition);

    expect($result['action'])->toBe('created')
        ->and($result['price']->nickname)->toBe('Monthly Plan');
});

it('archives and recreates when nickname changes', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'monthly',
        'provider_id' => 'price_old',
        'amount' => 1000,
        'currency' => 'eur',
        'nickname' => 'Old Nickname',
        'active' => true,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_old')
        ->andReturn((object) ['id' => 'price_old']);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null, 'New Nickname')
        ->andReturn('price_new');

    $service = new PriceService($client);
    $definition = new PriceDefinition(amount: 1000, nickname: 'New Nickname');

    $result = $service->sync($product, 'monthly', $definition);

    expect($result['action'])->toBe('updated')
        ->and($result['new']->nickname)->toBe('New Nickname');
});
