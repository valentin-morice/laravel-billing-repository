<?php

use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractDetectStage;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractPipelineStage;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractProcessStage;

/**
 * Pipeline Architecture Tests
 *
 * These tests enforce the pipeline pattern architecture to prevent regressions
 * and ensure consistency across all pipeline stages.
 */

// All concrete pipeline stages must extend AbstractPipelineStage
arch('all pipeline stages extend base class')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price')
    ->classes()
    ->toExtend(AbstractPipelineStage::class)
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product')
    ->classes()
    ->toExtend(AbstractPipelineStage::class)
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Resource')
    ->classes()
    ->toExtend(AbstractPipelineStage::class);

// All Process stages must extend AbstractProcessStage
arch('all process stages extend AbstractProcessStage')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price\ProcessPriceChangesStage')
    ->toExtend(AbstractProcessStage::class)
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product\ProcessProductChangesStage')
    ->toExtend(AbstractProcessStage::class)
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Resource\ProcessArchivedResourcesStage')
    ->toExtend(AbstractProcessStage::class);

// Product and Price detect stages must extend AbstractDetectStage
arch('detect stages with change detection extend AbstractDetectStage')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product\DetectProductChangesStage')
    ->toExtend(AbstractDetectStage::class)
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price\DetectPriceChangesStage')
    ->toExtend(AbstractDetectStage::class);

// Process stages must implement process() method
arch('process stages implement process method')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price\ProcessPriceChangesStage')
    ->toHaveMethod('process')
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product\ProcessProductChangesStage')
    ->toHaveMethod('process')
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Resource\ProcessArchivedResourcesStage')
    ->toHaveMethod('process');

// Detect stages must implement detect() method
arch('detect stages implement detect method')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product\DetectProductChangesStage')
    ->toHaveMethod('detect')
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price\DetectPriceChangesStage')
    ->toHaveMethod('detect');

// Pipeline stages should not use dd, dump, or ray
arch('pipeline stages do not use debugging functions')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline')
    ->not->toUse(['dd', 'dump', 'ray']);

// Abstract classes should be in Abstract namespace
arch('abstract pipeline classes are in Abstract namespace')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract')
    ->classes()
    ->toBeAbstract();

// All concrete stages must have proper naming convention
arch('pipeline stages follow naming convention')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price')
    ->classes()
    ->toHaveSuffix('Stage')
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product')
    ->classes()
    ->toHaveSuffix('Stage')
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Resource')
    ->classes()
    ->toHaveSuffix('Stage');

// Process stages should only depend on abstractions
arch('process stages depend on interfaces')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price\ProcessPriceChangesStage')
    ->toOnlyUse([
        'ValentinMorice\LaravelBillingRepository\Contracts',
        'ValentinMorice\LaravelBillingRepository\Data',
        'ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract',
        'Illuminate\Support',
    ])
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product\ProcessProductChangesStage')
    ->toOnlyUse([
        'ValentinMorice\LaravelBillingRepository\Contracts',
        'ValentinMorice\LaravelBillingRepository\Data',
        'ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract',
        'Illuminate\Support',
    ]);

// Ensure proper file organization
arch('pipeline stages are in correct directories')
    ->expect('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price')
    ->toBeClasses()
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product')
    ->toBeClasses()
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Resource')
    ->toBeClasses()
    ->and('ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract')
    ->toBeClasses();
