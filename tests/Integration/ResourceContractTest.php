<?php

use ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\ProductResourceInterface;
use ValentinMorice\LaravelBillingRepository\Stripe\Resources\PriceResource;
use ValentinMorice\LaravelBillingRepository\Stripe\Resources\ProductResource;

it('product resource implements product resource interface', function () {
    $resource = new ProductResource;

    expect($resource)->toBeInstanceOf(ProductResourceInterface::class);
});

it('price resource implements price resource interface', function () {
    $resource = new PriceResource;

    expect($resource)->toBeInstanceOf(PriceResourceInterface::class);
});

it('product resource has all required methods', function () {
    $resource = new ProductResource;

    expect(method_exists($resource, 'create'))->toBeTrue()
        ->and(method_exists($resource, 'update'))->toBeTrue()
        ->and(method_exists($resource, 'archive'))->toBeTrue();
});

it('price resource has all required methods', function () {
    $resource = new PriceResource;

    expect(method_exists($resource, 'create'))->toBeTrue()
        ->and(method_exists($resource, 'archive'))->toBeTrue();
});

it('product resource create method signature is correct', function () {
    $reflection = new ReflectionClass(ProductResource::class);
    $method = $reflection->getMethod('create');

    expect($method->getNumberOfParameters())->toBe(2)
        ->and($method->getNumberOfRequiredParameters())->toBe(1);
});

it('product resource update method signature is correct', function () {
    $reflection = new ReflectionClass(ProductResource::class);
    $method = $reflection->getMethod('update');

    expect($method->getNumberOfParameters())->toBe(2)
        ->and($method->getNumberOfRequiredParameters())->toBe(2);
});

it('product resource archive method signature is correct', function () {
    $reflection = new ReflectionClass(ProductResource::class);
    $method = $reflection->getMethod('archive');

    expect($method->getNumberOfParameters())->toBe(1)
        ->and($method->getNumberOfRequiredParameters())->toBe(1);
});

it('price resource create method signature is correct', function () {
    $reflection = new ReflectionClass(PriceResource::class);
    $method = $reflection->getMethod('create');

    expect($method->getNumberOfParameters())->toBe(5)
        ->and($method->getNumberOfRequiredParameters())->toBe(3);
});

it('price resource archive method signature is correct', function () {
    $reflection = new ReflectionClass(PriceResource::class);
    $method = $reflection->getMethod('archive');

    expect($method->getNumberOfParameters())->toBe(1)
        ->and($method->getNumberOfRequiredParameters())->toBe(1);
});
