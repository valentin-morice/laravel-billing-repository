<?php

use ValentinMorice\LaravelBillingRepository\Deployer\Actions\CreatePriceComparisonObjectAction;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripePriceFeatures;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    $this->extractor = new StripeFeatureExtractor;
    $this->action = new CreatePriceComparisonObjectAction($this->extractor);
});

it('creates comparison object with all universal fields', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 1999,
        'currency' => 'usd',
        'recurring' => ['interval' => 'month'],
        'nickname' => 'Monthly Plan',
        'metadata' => ['tier' => 'premium', 'featured' => true],
        'trial_period_days' => 14,
    ]);

    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and($result->amount)->toBe(1999)
        ->and($result->currency)->toBe('usd')
        ->and($result->recurring)->toBe(['interval' => 'month'])
        ->and($result->nickname)->toBe('Monthly Plan')
        ->and($result->metadata)->toBe(['tier' => 'premium', 'featured' => true])
        ->and($result->trialPeriodDays)->toBe(14);
});

it('creates comparison object with stripe features', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 2999,
        'currency' => 'eur',
        'recurring' => ['interval' => 'year'],
    ]);

    StripePriceFeatures::create([
        'billing_price_id' => $price->id,
        'tax_behavior' => 'exclusive',
        'lookup_key' => 'enterprise_yearly',
    ]);

    $price->load('stripe');
    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and($result->amount)->toBe(2999)
        ->and($result->currency)->toBe('eur')
        ->and($result->stripe)->toBeObject()
        ->and($result->stripe->tax_behavior)->toBe('exclusive')
        ->and($result->stripe->lookup_key)->toBe('enterprise_yearly');
});

it('creates comparison object without stripe when no features exist', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 999,
        'currency' => 'gbp',
    ]);

    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and($result->amount)->toBe(999)
        ->and($result->currency)->toBe('gbp')
        ->and($result->stripe)->toBeNull();
});

it('handles null recurring correctly', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 4999,
        'currency' => 'eur',
        'recurring' => null,
    ]);

    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and($result->recurring)->toBeNull();
});

it('handles null nickname correctly', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 1500,
        'nickname' => null,
    ]);

    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and($result->nickname)->toBeNull();
});

it('handles null metadata correctly', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 1200,
        'metadata' => null,
    ]);

    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and($result->metadata)->toBeNull();
});

it('handles null trial period days correctly', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 999,
        'trial_period_days' => null,
    ]);

    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and($result->trialPeriodDays)->toBeNull();
});

it('creates comparison object with partial stripe features', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 3999,
    ]);

    StripePriceFeatures::create([
        'billing_price_id' => $price->id,
        'tax_behavior' => 'inclusive',
        'lookup_key' => null,
    ]);

    $price->load('stripe');
    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and($result->stripe)->toBeObject()
        ->and($result->stripe->tax_behavior)->toBe('inclusive')
        ->and(property_exists($result->stripe, 'lookup_key'))->toBeFalse();
});

it('uses provider name from extractor for key', function () {
    $price = BillingPrice::factory()->create();
    StripePriceFeatures::create([
        'billing_price_id' => $price->id,
        'lookup_key' => 'test_key',
    ]);

    $price->load('stripe');
    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and(property_exists($result, 'stripe'))->toBeTrue()
        ->and($this->extractor->getProviderName())->toBe('stripe');
});

it('converts trial_period_days to camelCase in comparison object', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 999,
        'trial_period_days' => 30,
    ]);

    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and(property_exists($result, 'trialPeriodDays'))->toBeTrue()
        ->and($result->trialPeriodDays)->toBe(30)
        ->and(property_exists($result, 'trial_period_days'))->toBeFalse();
});

it('handles one-time payment prices without recurring', function () {
    $price = BillingPrice::factory()->create([
        'amount' => 9999,
        'currency' => 'usd',
        'recurring' => null,
    ]);

    $result = $this->action->handle($price);

    expect($result)->toBeObject()
        ->and($result->amount)->toBe(9999)
        ->and($result->currency)->toBe('usd')
        ->and($result->recurring)->toBeNull();
});
