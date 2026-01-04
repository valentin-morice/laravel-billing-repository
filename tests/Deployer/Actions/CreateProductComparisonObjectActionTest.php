<?php

use ValentinMorice\LaravelBillingRepository\Deployer\Actions\CreateProductComparisonObjectAction;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripeProductFeatures;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    $this->extractor = new StripeFeatureExtractor;
    $this->action = new CreateProductComparisonObjectAction($this->extractor);
});

it('creates comparison object with all universal fields', function () {
    $product = BillingProduct::factory()->create([
        'name' => 'Premium Plan',
        'description' => 'Full-featured premium',
        'metadata' => ['tier' => 'premium', 'featured' => true],
    ]);

    $result = $this->action->handle($product);

    expect($result)->toBeObject()
        ->and($result->name)->toBe('Premium Plan')
        ->and($result->description)->toBe('Full-featured premium')
        ->and($result->metadata)->toBe(['tier' => 'premium', 'featured' => true]);
});

it('creates comparison object with stripe features', function () {
    $product = BillingProduct::factory()->create([
        'name' => 'Enterprise Plan',
        'description' => 'For large teams',
        'metadata' => ['tier' => 'enterprise'],
    ]);

    StripeProductFeatures::create([
        'billing_product_id' => $product->id,
        'tax_code' => 'txcd_10000000',
        'statement_descriptor' => 'MYAPP ENT',
    ]);

    $product->load('stripe');
    $result = $this->action->handle($product);

    expect($result)->toBeObject()
        ->and($result->name)->toBe('Enterprise Plan')
        ->and($result->description)->toBe('For large teams')
        ->and($result->metadata)->toBe(['tier' => 'enterprise'])
        ->and($result->stripe)->toBeObject()
        ->and($result->stripe->tax_code)->toBe('txcd_10000000')
        ->and($result->stripe->statement_descriptor)->toBe('MYAPP ENT');
});

it('creates comparison object without stripe when no features exist', function () {
    $product = BillingProduct::factory()->create([
        'name' => 'Basic Plan',
        'description' => 'Simple plan',
    ]);

    $result = $this->action->handle($product);

    expect($result)->toBeObject()
        ->and($result->name)->toBe('Basic Plan')
        ->and($result->description)->toBe('Simple plan')
        ->and($result->stripe)->toBeNull();
});

it('handles null description correctly', function () {
    $product = BillingProduct::factory()->create([
        'name' => 'Test Product',
        'description' => null,
    ]);

    $result = $this->action->handle($product);

    expect($result)->toBeObject()
        ->and($result->name)->toBe('Test Product')
        ->and($result->description)->toBeNull();
});

it('handles null metadata correctly', function () {
    $product = BillingProduct::factory()->create([
        'name' => 'Test Product',
        'metadata' => null,
    ]);

    $result = $this->action->handle($product);

    expect($result)->toBeObject()
        ->and($result->name)->toBe('Test Product')
        ->and($result->metadata)->toBeNull();
});

it('creates comparison object with partial stripe features', function () {
    $product = BillingProduct::factory()->create([
        'name' => 'Partial Plan',
    ]);

    StripeProductFeatures::create([
        'billing_product_id' => $product->id,
        'tax_code' => 'txcd_20000000',
        'statement_descriptor' => null,
    ]);

    $product->load('stripe');
    $result = $this->action->handle($product);

    expect($result)->toBeObject()
        ->and($result->stripe)->toBeObject()
        ->and($result->stripe->tax_code)->toBe('txcd_20000000')
        ->and(property_exists($result->stripe, 'statement_descriptor'))->toBeFalse();
});

it('uses provider name from extractor for key', function () {
    $product = BillingProduct::factory()->create();
    StripeProductFeatures::create([
        'billing_product_id' => $product->id,
        'tax_code' => 'txcd_30000000',
    ]);

    $product->load('stripe');
    $result = $this->action->handle($product);

    expect($result)->toBeObject()
        ->and(property_exists($result, 'stripe'))->toBeTrue()
        ->and($this->extractor->getProviderName())->toBe('stripe');
});
