<?php

use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripePriceFeatures;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripeProductFeatures;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    $this->extractor = new StripeFeatureExtractor;
});

it('returns stripe as provider name', function () {
    expect($this->extractor->getProviderName())->toBe('stripe');
});

it('extracts price features when stripe relationship exists with all fields', function () {
    $price = BillingPrice::factory()->create();
    StripePriceFeatures::create([
        'billing_price_id' => $price->id,
        'tax_behavior' => 'exclusive',
        'lookup_key' => 'premium_monthly',
    ]);

    $price->load('stripe');
    $result = $this->extractor->extractPriceFeatures($price);

    expect($result)->toBeObject()
        ->and($result->tax_behavior)->toBe('exclusive')
        ->and($result->lookup_key)->toBe('premium_monthly');
});

it('extracts price features when stripe relationship exists with partial fields', function () {
    $price = BillingPrice::factory()->create();
    StripePriceFeatures::create([
        'billing_price_id' => $price->id,
        'tax_behavior' => 'inclusive',
        'lookup_key' => null,
    ]);

    $price->load('stripe');
    $result = $this->extractor->extractPriceFeatures($price);

    expect($result)->toBeObject()
        ->and($result->tax_behavior)->toBe('inclusive')
        ->and(property_exists($result, 'lookup_key'))->toBeFalse();
});

it('returns null when price has no stripe relationship', function () {
    $price = BillingPrice::factory()->create();

    $result = $this->extractor->extractPriceFeatures($price);

    expect($result)->toBeNull();
});

it('returns null when price stripe relationship has all null fields', function () {
    $price = BillingPrice::factory()->create();
    StripePriceFeatures::create([
        'billing_price_id' => $price->id,
        'tax_behavior' => null,
        'lookup_key' => null,
    ]);

    $price->load('stripe');
    $result = $this->extractor->extractPriceFeatures($price);

    expect($result)->toBeNull();
});

it('extracts product features when stripe relationship exists with all fields', function () {
    $product = BillingProduct::factory()->create();
    StripeProductFeatures::create([
        'billing_product_id' => $product->id,
        'tax_code' => 'txcd_10000000',
        'statement_descriptor' => 'MYAPP PREMIUM',
    ]);

    $product->load('stripe');
    $result = $this->extractor->extractProductFeatures($product);

    expect($result)->toBeObject()
        ->and($result->tax_code)->toBe('txcd_10000000')
        ->and($result->statement_descriptor)->toBe('MYAPP PREMIUM');
});

it('extracts product features when stripe relationship exists with partial fields', function () {
    $product = BillingProduct::factory()->create();
    StripeProductFeatures::create([
        'billing_product_id' => $product->id,
        'tax_code' => 'txcd_99999999',
        'statement_descriptor' => null,
    ]);

    $product->load('stripe');
    $result = $this->extractor->extractProductFeatures($product);

    expect($result)->toBeObject()
        ->and($result->tax_code)->toBe('txcd_99999999')
        ->and(property_exists($result, 'statement_descriptor'))->toBeFalse();
});

it('returns null when product has no stripe relationship', function () {
    $product = BillingProduct::factory()->create();

    $result = $this->extractor->extractProductFeatures($product);

    expect($result)->toBeNull();
});

it('returns null when product stripe relationship has all null fields', function () {
    $product = BillingProduct::factory()->create();
    StripeProductFeatures::create([
        'billing_product_id' => $product->id,
        'tax_code' => null,
        'statement_descriptor' => null,
    ]);

    $product->load('stripe');
    $result = $this->extractor->extractProductFeatures($product);

    expect($result)->toBeNull();
});
