<?php

use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Contracts\ProductResourceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product\ArchiveAction;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
});

afterEach(function () {
    m::close();
});

it('archives product and stores in database', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('archive')
        ->once()
        ->with('prod_123')
        ->andReturn((object) ['id' => 'prod_123', 'active' => false]);

    $action = new ArchiveAction($client);
    $result = $action->handle($product);

    expect($result)->toBeInstanceOf(BillingProduct::class)
        ->and($result->active)->toBeFalse()
        ->and($result->provider_id)->toBe('prod_123')
        ->and(BillingProduct::where('active', false)->count())->toBe(1);
});

it('preserves other product attributes when archiving', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'description' => 'A test product',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('archive')
        ->once()
        ->with('prod_123')
        ->andReturn((object) ['id' => 'prod_123', 'active' => false]);

    $action = new ArchiveAction($client);
    $result = $action->handle($product);

    expect($result->key)->toBe('test_product')
        ->and($result->name)->toBe('Test Product')
        ->and($result->description)->toBe('A test product')
        ->and($result->active)->toBeFalse();
});

it('throws exception when provider API fails', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    $productResource = m::mock(ProductResourceInterface::class);
    $client = m::mock(ProviderClientInterface::class);
    $client->shouldReceive('product')->andReturn($productResource);

    $productResource->shouldReceive('archive')
        ->once()
        ->andThrow(new \Exception('Stripe API error'));

    $action = new ArchiveAction($client);

    expect(fn () => $action->handle($product))
        ->toThrow(\Exception::class, 'Stripe API error');

    // Verify database was not updated
    $product->refresh();
    expect($product->active)->toBeTrue();
});
