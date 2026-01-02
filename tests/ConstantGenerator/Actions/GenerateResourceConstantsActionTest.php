<?php

use ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions\ConvertToConstantNameAction;
use ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions\GenerateResourceConstantsAction;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ModelType;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

it('generates constants from active products only', function () {
    BillingProduct::create([
        'key' => 'nif',
        'provider_id' => 'prod_123',
        'name' => 'NIF Portugal',
        'active' => true,
    ]);

    BillingProduct::create([
        'key' => 'social_sec',
        'provider_id' => 'prod_456',
        'name' => 'Social Security',
        'active' => true,
    ]);

    BillingProduct::create([
        'key' => 'archived_product',
        'provider_id' => 'prod_789',
        'name' => 'Archived',
        'active' => false,
    ]);

    $action = new GenerateResourceConstantsAction(new ConvertToConstantNameAction);
    $result = $action->handle(ModelType::Product);

    expect($result)->toBe([
        'nif' => 'NIF',
        'social_sec' => 'SOCIAL_SEC',
    ]);
});

it('returns array mapping product keys to constant names', function () {
    BillingProduct::create([
        'key' => 'premium',
        'provider_id' => 'prod_abc',
        'name' => 'Premium Plan',
        'active' => true,
    ]);

    $action = new GenerateResourceConstantsAction(new ConvertToConstantNameAction);
    $result = $action->handle(ModelType::Product);

    expect($result)->toBeArray()
        ->toHaveKey('premium')
        ->and($result['premium'])->toBe('PREMIUM');
});

it('handles empty product database gracefully', function () {
    $action = new GenerateResourceConstantsAction(new ConvertToConstantNameAction);
    $result = $action->handle(ModelType::Product);

    expect($result)->toBe([]);
});

it('sorts product results consistently by key', function () {
    BillingProduct::create([
        'key' => 'zebra',
        'provider_id' => 'prod_1',
        'name' => 'Zebra',
        'active' => true,
    ]);

    BillingProduct::create([
        'key' => 'alpha',
        'provider_id' => 'prod_2',
        'name' => 'Alpha',
        'active' => true,
    ]);

    BillingProduct::create([
        'key' => 'beta',
        'provider_id' => 'prod_3',
        'name' => 'Beta',
        'active' => true,
    ]);

    $action = new GenerateResourceConstantsAction(new ConvertToConstantNameAction);
    $result = $action->handle(ModelType::Product);

    expect(array_keys($result))->toBe(['alpha', 'beta', 'zebra']);
});

it('generates constants from active price types only', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'default',
        'provider_id' => 'price_123',
        'amount' => 1000,
        'currency' => 'eur',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'monthly',
        'provider_id' => 'price_456',
        'amount' => 2000,
        'currency' => 'eur',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'archived',
        'provider_id' => 'price_789',
        'amount' => 3000,
        'currency' => 'eur',
        'active' => false,
    ]);

    $action = new GenerateResourceConstantsAction(new ConvertToConstantNameAction);
    $result = $action->handle(ModelType::Price);

    expect($result)->toBe([
        'default' => 'DEFAULT_CONST',
        'monthly' => 'MONTHLY',
    ]);
});

it('returns distinct price types', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    // Create multiple prices with the same type
    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'monthly',
        'provider_id' => 'price_123',
        'amount' => 1000,
        'currency' => 'eur',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'yearly',
        'provider_id' => 'price_456',
        'amount' => 2000,
        'currency' => 'usd',
        'active' => true,
    ]);

    $action = new GenerateResourceConstantsAction(new ConvertToConstantNameAction);
    $result = $action->handle(ModelType::Price);

    expect($result)->toBe([
        'monthly' => 'MONTHLY',
        'yearly' => 'YEARLY',
    ]);
});

it('handles empty price database gracefully', function () {
    $action = new GenerateResourceConstantsAction(new ConvertToConstantNameAction);
    $result = $action->handle(ModelType::Price);

    expect($result)->toBe([]);
});

it('sorts price results consistently by type', function () {
    $product = BillingProduct::create([
        'key' => 'test_product',
        'provider_id' => 'prod_123',
        'name' => 'Test Product',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'zero',
        'provider_id' => 'price_1',
        'amount' => 0,
        'currency' => 'eur',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'annual',
        'provider_id' => 'price_2',
        'amount' => 10000,
        'currency' => 'eur',
        'active' => true,
    ]);

    BillingPrice::create([
        'product_id' => $product->id,
        'type' => 'monthly',
        'provider_id' => 'price_3',
        'amount' => 1000,
        'currency' => 'eur',
        'active' => true,
    ]);

    $action = new GenerateResourceConstantsAction(new ConvertToConstantNameAction);
    $result = $action->handle(ModelType::Price);

    expect(array_keys($result))->toBe(['annual', 'monthly', 'zero']);
});
