<?php

use ValentinMorice\LaravelBillingRepository\Exceptions\Models\ProductNotFoundException;
use ValentinMorice\LaravelBillingRepository\Facades\BillingRepository;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

it('facade priceId method works', function () {
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

it('facade productId method works', function () {
    BillingProduct::create([
        'key' => 'premium',
        'provider_id' => 'prod_abc',
        'name' => 'Premium Plan',
        'active' => true,
    ]);

    $productId = BillingRepository::productId('premium');

    expect($productId)->toBe('prod_abc');
});

it('facade prices method works', function () {
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
        'type' => 'monthly',
        'provider_id' => 'price_2',
        'amount' => 2000,
        'currency' => 'eur',
        'active' => true,
    ]);

    $prices = BillingRepository::prices('nif');

    expect($prices)->toHaveCount(2)
        ->and($prices->pluck('type')->all())->toBe(['default', 'monthly']);
});

it('facade product method works', function () {
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

it('facade throws exception for missing product', function () {
    BillingProduct::create([
        'key' => 'nif',
        'provider_id' => 'prod_123',
        'name' => 'NIF',
        'active' => true,
    ]);

    expect(fn () => BillingRepository::productId('nonexistent'))
        ->toThrow(ProductNotFoundException::class, "Product 'nonexistent' not found");
});
