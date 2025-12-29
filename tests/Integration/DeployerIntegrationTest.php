<?php

/** @noinspection PhpUnhandledExceptionInspection */

use ValentinMorice\LaravelBillingRepository\Deployer\DeployerService;

beforeEach(function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    config(['billing.provider' => 'stripe']);
});

it('can resolve deployer from container', function () {
    $deployer = app(DeployerService::class);

    expect($deployer)->toBeInstanceOf(DeployerService::class);
});

it('deployer receives correctly resolved action', function () {
    $deployer = app(DeployerService::class);

    // Use reflection to verify the action is set
    $reflection = new ReflectionClass($deployer);

    $buildChangeSetProperty = $reflection->getProperty('buildChangeSet');
    $buildChangeSet = $buildChangeSetProperty->getValue($deployer);

    expect($buildChangeSet)->toBeInstanceOf(\ValentinMorice\LaravelBillingRepository\Deployer\Actions\BuildChangeSetAction::class);
});

it('can call deploy method on resolved deployer', function () {
    config(['billing.products' => []]);

    $deployer = app(DeployerService::class);
    $changeSet = $deployer->deploy();

    expect($changeSet)->toBeInstanceOf(\ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet::class);

    $summary = $changeSet->getSummary();

    expect($summary)->toBeArray()
        ->and($summary)->toHaveKeys(['products', 'prices'])
        ->and($summary['products'])->toHaveKeys(['created', 'updated', 'unchanged', 'archived'])
        ->and($summary['prices'])->toHaveKeys(['created', 'updated', 'unchanged', 'archived']);
});

it('deployer uses stripe services when provider is stripe', function () {
    config(['billing.provider' => 'stripe']);

    app()->forgetInstance(DeployerService::class);
    app()->forgetInstance(\ValentinMorice\LaravelBillingRepository\Deployer\Actions\BuildChangeSetAction::class);
    app()->forgetInstance(\ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface::class);
    app()->forgetInstance(\ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface::class);

    $action = app(\ValentinMorice\LaravelBillingRepository\Deployer\Actions\BuildChangeSetAction::class);

    $reflection = new ReflectionClass($action);
    $pipelineProperty = $reflection->getProperty('pipeline');
    $pipeline = $pipelineProperty->getValue($action);

    expect($pipeline)->toBeInstanceOf(\Illuminate\Pipeline\Pipeline::class);

    // Verify that the correct service implementations are bound
    $productService = app(\ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface::class);
    expect($productService)->toBeInstanceOf(\ValentinMorice\LaravelBillingRepository\Stripe\Services\ProductService::class);
});
