<?php

use ValentinMorice\LaravelBillingRepository\Facades\BillingRepository;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

it('retrieves price ID using product key and price type', function () {
    $product = BillingProduct::create([
        'key' => 'nif',
        'provider_id' => 'prod_123',
        'name' => 'NIF Portugal',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_456',
        'amount' => 12000,
        'currency' => 'eur',
        'active' => true,
    ]);

    $priceId = BillingRepository::priceId('nif', 'default');

    expect($priceId)->toBe('price_456');
});

it('retrieves product ID using product key', function () {
    BillingProduct::create([
        'key' => 'premium',
        'provider_id' => 'prod_abc',
        'name' => 'Premium Plan',
        'active' => true,
    ]);

    $productId = BillingRepository::productId('premium');

    expect($productId)->toBe('prod_abc');
});

it('gets all active prices for a product', function () {
    $product = BillingProduct::create([
        'key' => 'nif',
        'provider_id' => 'prod_123',
        'name' => 'NIF Portugal',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_1',
        'amount' => 12000,
        'currency' => 'eur',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'zero',
        'provider_id' => 'price_2',
        'amount' => 0,
        'currency' => 'eur',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'archived',
        'provider_id' => 'price_3',
        'amount' => 5000,
        'currency' => 'eur',
        'active' => false,
    ]);

    $prices = BillingRepository::prices('nif');

    expect($prices)->toHaveCount(2)
        ->and($prices->pluck('type')->toArray())->toBe(['default', 'zero']);
});

it('gets product model with relationships', function () {
    $product = BillingProduct::create([
        'key' => 'nif',
        'provider_id' => 'prod_123',
        'name' => 'NIF Portugal',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_456',
        'amount' => 12000,
        'currency' => 'eur',
        'active' => true,
    ]);

    $retrievedProduct = BillingRepository::product('nif');

    expect($retrievedProduct)->toBeInstanceOf(BillingProduct::class)
        ->and($retrievedProduct->key)->toBe('nif')
        ->and($retrievedProduct->relationLoaded('prices'))->toBeTrue();
});

it('throws exception for missing product', function () {
    BillingProduct::create([
        'key' => 'nif',
        'provider_id' => 'prod_123',
        'name' => 'NIF Portugal',
        'active' => true,
    ]);

    expect(fn () => BillingRepository::productId('nonexistent'))
        ->toThrow(\InvalidArgumentException::class, "Product 'nonexistent' not found");
});

it('throws exception for missing price', function () {
    $product = BillingProduct::create([
        'key' => 'nif',
        'provider_id' => 'prod_123',
        'name' => 'NIF Portugal',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_456',
        'amount' => 12000,
        'currency' => 'eur',
        'active' => true,
    ]);

    expect(fn () => BillingRepository::priceId('nif', 'nonexistent'))
        ->toThrow(\InvalidArgumentException::class, "Price 'nonexistent' not found for product 'nif'");
});

it('ignores inactive products', function () {
    BillingProduct::create([
        'key' => 'active_product',
        'provider_id' => 'prod_123',
        'name' => 'Active',
        'active' => true,
    ]);

    BillingProduct::create([
        'key' => 'inactive_product',
        'provider_id' => 'prod_456',
        'name' => 'Inactive',
        'active' => false,
    ]);

    expect(fn () => BillingRepository::productId('inactive_product'))
        ->toThrow(\InvalidArgumentException::class);
});

it('ignores inactive prices', function () {
    $product = BillingProduct::create([
        'key' => 'nif',
        'provider_id' => 'prod_123',
        'name' => 'NIF Portugal',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'inactive_price',
        'provider_id' => 'price_456',
        'amount' => 12000,
        'currency' => 'eur',
        'active' => false,
    ]);

    expect(fn () => BillingRepository::priceId('nif', 'inactive_price'))
        ->toThrow(\InvalidArgumentException::class);
});
