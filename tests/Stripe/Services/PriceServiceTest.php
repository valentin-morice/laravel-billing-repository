<?php

use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\RecurringConfig;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceArchiveResult;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceSyncResult;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;
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
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null, null, null, null, null, null)
        ->andReturn('price_456');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(amount: 1000);

    $result = $service->sync($product, 'default', $definition);

    expect($result)->toBeInstanceOf(PriceSyncResult::class)
        ->and($result->wasCreated())->toBeTrue()
        ->and($result->price)->toBeInstanceOf(BillingPrice::class)
        ->and($result->price->amount)->toBe(1000)
        ->and($result->price->currency)->toBe('eur')
        ->and(BillingPrice::count())->toBe(1);
});

it('creates recurring price with interval', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'subscription',
        'provider_id' => 'prod_sub',
        'name' => 'Subscription',
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_sub', 999, 'eur', ['interval' => 'month'], null, null, null, null, null)
        ->andReturn('price_monthly');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(
        amount: 999,
        recurring: new RecurringConfig('month')
    );

    $result = $service->sync($product, 'monthly', $definition);

    expect($result->wasCreated())->toBeTrue()
        ->and($result->price->recurring)->toBe(['interval' => 'month'])
        ->and($result->price->key)->toBe('monthly');
});

it('returns unchanged when price already exists with same data', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'default',
        'provider_id' => 'price_existing',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => null,
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldNotReceive('create');
    $priceResource->shouldNotReceive('archive');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(amount: 1000);

    $result = $service->sync($product, 'default', $definition);

    expect($result->wasUnchanged())->toBeTrue()
        ->and(BillingPrice::count())->toBe(1);
});

it('archives old price and creates new when amount changes', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'default',
        'provider_id' => 'price_old',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => null,
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
        ->with('prod_123', 2000, 'eur', null, null, null, null, null, null)
        ->andReturn('price_new');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(amount: 2000);

    $result = $service->sync($product, 'default', $definition);

    expect($result->wasUpdated())->toBeTrue()
        ->and($result->hasChanges())->toBeTrue()
        ->and($result->oldPrice)->toBeInstanceOf(BillingPrice::class)
        ->and($result->price)->toBeInstanceOf(BillingPrice::class)
        ->and($result->oldPrice->active)->toBeFalse()
        ->and($result->price->active)->toBeTrue()
        ->and($result->price->amount)->toBe(2000)
        ->and(BillingPrice::count())->toBe(2)
        ->and(BillingPrice::where('active', true)->count())->toBe(1);
});

it('archives and recreates when currency changes', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'default',
        'provider_id' => 'price_old',
        'amount' => 1000,
        'currency' => 'eur',
        'recurring' => null,
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
        ->with('prod_123', 1000, 'usd', null, null, null, null, null, null)
        ->andReturn('price_new');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(amount: 1000, currency: 'usd');

    $result = $service->sync($product, 'default', $definition);

    expect($result->wasUpdated())->toBeTrue()
        ->and($result->hasChanges())->toBeTrue()
        ->and($result->price->currency)->toBe('usd');
});

it('archives and recreates when recurring interval changes', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'subscription',
        'provider_id' => 'prod_sub',
        'name' => 'Subscription',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'subscription',
        'provider_id' => 'price_old',
        'amount' => 999,
        'currency' => 'eur',
        'recurring' => ['interval' => 'month'],
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
        ->with('prod_sub', 999, 'eur', ['interval' => 'year'], null, null, null, null, null)
        ->andReturn('price_new');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(
        amount: 999,
        recurring: new RecurringConfig('year')
    );

    $result = $service->sync($product, 'subscription', $definition);

    expect($result->wasUpdated())->toBeTrue()
        ->and($result->hasChanges())->toBeTrue()
        ->and($result->price->recurring)->toBe(['interval' => 'year']);
});

it('handles multiple price types for same product', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->twice()
        ->withArgs(fn ($productId, $amount, $currency, $recurring, $nickname) => true)
        ->andReturn('price_default', 'price_premium');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);

    $defaultResult = $service->sync($product, 'default', new PriceDefinition(1000));
    $premiumResult = $service->sync($product, 'premium', new PriceDefinition(2000));

    expect($defaultResult->price->key)->toBe('default')
        ->and($premiumResult->price->key)->toBe('premium')
        ->and(BillingPrice::count())->toBe(2);
});

it('archives removed prices not in configured list', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'monthly',
        'provider_id' => 'price_monthly',
        'amount' => 1000,
        'currency' => 'eur',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'yearly',
        'provider_id' => 'price_yearly',
        'amount' => 10000,
        'currency' => 'eur',
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_yearly')
        ->andReturn((object) ['id' => 'price_yearly']);

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $result = $service->archiveRemoved($product, ['monthly']);

    expect($result)->toBeInstanceOf(PriceArchiveResult::class)
        ->and($result->count)->toBe(1)
        ->and($result->archivedPrices)->toHaveCount(1)
        ->and(BillingPrice::where('active', true)->count())->toBe(1)
        ->and(BillingPrice::where('active', false)->count())->toBe(1);
});

it('does not archive prices that are in configured list', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'monthly',
        'provider_id' => 'price_monthly',
        'amount' => 1000,
        'currency' => 'eur',
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldNotReceive('archive');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $result = $service->archiveRemoved($product, ['monthly']);

    expect($result)->toBeInstanceOf(PriceArchiveResult::class)
        ->and($result->count)->toBe(0)
        ->and(BillingPrice::where('active', true)->count())->toBe(1);
});

it('creates price with nickname', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1000, 'eur', null, 'Monthly Plan', null, null, null, null)
        ->andReturn('price_456');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(amount: 1000, nickname: 'Monthly Plan');

    $result = $service->sync($product, 'default', $definition);

    expect($result->wasCreated())->toBeTrue()
        ->and($result->price->nickname)->toBe('Monthly Plan');
});

it('updates in-place when only nickname changes', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'monthly',
        'provider_id' => 'price_old',
        'amount' => 1000,
        'currency' => 'eur',
        'nickname' => 'Old Nickname',
    ]);

    $priceResource = m::mock(PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('update')
        ->once()
        ->with('price_old', ['nickname' => 'New Nickname'])
        ->andReturn((object) ['id' => 'price_old']);

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(amount: 1000, nickname: 'New Nickname');

    $result = $service->sync($product, 'monthly', $definition);

    expect($result->wasUpdated())->toBeTrue()
        ->and($result->hasChanges())->toBeTrue()
        ->and($result->price->nickname)->toBe('New Nickname');
});

it('duplicates price with new key when using duplicate strategy', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'monthly',
        'provider_id' => 'price_old',
        'amount' => 999,
        'currency' => 'eur',
        'recurring' => ['interval' => 'month'],
    ]);

    $priceResource = m::mock(\ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    // Should NOT archive the old price
    $priceResource->shouldNotReceive('archive');

    // Should create new price with new key
    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1299, 'eur', ['interval' => 'month'], null, null, null, null, null)
        ->andReturn('price_new');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(
        amount: 1299,
        recurring: new RecurringConfig('month')
    );

    $result = $service->sync(
        $product,
        'monthly',
        $definition,
        \ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy::Duplicate,
        'monthly_v2'
    );

    expect($result->wasDuplicated())->toBeTrue()
        ->and($result->wasCreated())->toBeTrue()
        ->and($result->price->key)->toBe('monthly_v2')
        ->and($result->price->amount)->toBe(1299)
        ->and($result->oldPrice)->toBeInstanceOf(BillingPrice::class)
        ->and($result->oldPrice->key)->toBe('monthly')
        ->and($result->oldPrice->active)->toBeTrue() // Old price should still be active
        ->and(BillingPrice::count())->toBe(2)
        ->and(BillingPrice::where('active', true)->count())->toBe(2); // Both prices active
});

it('uses archive strategy by default when immutable fields change', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'monthly',
        'provider_id' => 'price_old',
        'amount' => 999,
        'currency' => 'eur',
        'recurring' => ['interval' => 'month'],
    ]);

    $priceResource = m::mock(\ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    // Should archive the old price (default behavior)
    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_old')
        ->andReturn((object) ['id' => 'price_old', 'active' => false]);

    // Should create new price with same key
    $priceResource->shouldReceive('create')
        ->once()
        ->with('prod_123', 1299, 'eur', ['interval' => 'month'], null, null, null, null, null)
        ->andReturn('price_new');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(
        amount: 1299,
        recurring: new RecurringConfig('month')
    );

    // No strategy specified - should default to archive
    $result = $service->sync($product, 'monthly', $definition);

    expect($result->wasUpdated())->toBeTrue()
        ->and($result->wasDuplicated())->toBeFalse()
        ->and($result->price->key)->toBe('monthly')
        ->and($result->price->amount)->toBe(1299)
        ->and($result->oldPrice)->toBeInstanceOf(BillingPrice::class)
        ->and($result->oldPrice->active)->toBeFalse()
        ->and(BillingPrice::count())->toBe(2)
        ->and(BillingPrice::where('active', true)->count())->toBe(1);
});

it('uses archive strategy explicitly when specified', function () {
    $product = BillingProduct::factory()->create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
    ]);

    BillingPrice::factory()->forProduct($product)->create([
        'key' => 'monthly',
        'provider_id' => 'price_old',
        'amount' => 999,
        'currency' => 'eur',
        'recurring' => null,
    ]);

    $priceResource = m::mock(\ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('price')->andReturn($priceResource);

    $priceResource->shouldReceive('archive')
        ->once()
        ->with('price_old')
        ->andReturn((object) ['id' => 'price_old', 'active' => false]);

    $priceResource->shouldReceive('create')
        ->once()
        ->andReturn('price_new');

    $service = new PriceService($client, new DetectChangesAction, new \ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor, \ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields::class);
    $definition = new PriceDefinition(amount: 1299);

    $result = $service->sync(
        $product,
        'monthly',
        $definition,
        \ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy::Archive
    );

    expect($result->wasUpdated())->toBeTrue()
        ->and($result->wasDuplicated())->toBeFalse()
        ->and($result->oldPrice->active)->toBeFalse();
});
