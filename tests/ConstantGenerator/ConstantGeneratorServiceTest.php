<?php

use ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions\GenerateResourceConstantsAction;
use ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions\WriteConstantsToFileAction;
use ValentinMorice\LaravelBillingRepository\ConstantGenerator\ConstantGeneratorService;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ModelType;

it('generates constants for both products and prices', function () {
    $generateResourceConstants = Mockery::mock(GenerateResourceConstantsAction::class);
    $writeConstantsToFile = Mockery::mock(WriteConstantsToFileAction::class);

    $generateResourceConstants->shouldReceive('handle')
        ->once()
        ->with(ModelType::Product)
        ->andReturn(['nif' => 'NIF']);

    $generateResourceConstants->shouldReceive('handle')
        ->once()
        ->with(ModelType::Price)
        ->andReturn(['monthly' => 'MONTHLY']);

    $writeConstantsToFile->shouldReceive('handle')
        ->twice()
        ->andReturn(true);

    $service = new ConstantGeneratorService(
        $generateResourceConstants,
        $writeConstantsToFile
    );

    $result = $service->generate();

    expect($result)->toBeTrue();
});

it('returns false if product generation fails', function () {
    $generateResourceConstants = Mockery::mock(GenerateResourceConstantsAction::class);
    $writeConstantsToFile = Mockery::mock(WriteConstantsToFileAction::class);

    $generateResourceConstants->shouldReceive('handle')
        ->once()
        ->with(ModelType::Product)
        ->andReturn(['nif' => 'NIF']);

    $generateResourceConstants->shouldReceive('handle')
        ->once()
        ->with(ModelType::Price)
        ->andReturn(['monthly' => 'MONTHLY']);

    // Product write fails
    $writeConstantsToFile->shouldReceive('handle')
        ->once()
        ->withArgs(fn ($path) => str_ends_with($path, 'Models/BillingProduct.php'))
        ->andReturn(false);

    // Price write succeeds
    $writeConstantsToFile->shouldReceive('handle')
        ->once()
        ->withArgs(fn ($path) => str_ends_with($path, 'Models/BillingPrice.php'))
        ->andReturn(true);

    $service = new ConstantGeneratorService(
        $generateResourceConstants,
        $writeConstantsToFile
    );

    $result = $service->generate();

    expect($result)->toBeFalse();
});

it('returns false if price generation fails', function () {
    $generateResourceConstants = Mockery::mock(GenerateResourceConstantsAction::class);
    $writeConstantsToFile = Mockery::mock(WriteConstantsToFileAction::class);

    $generateResourceConstants->shouldReceive('handle')
        ->once()
        ->with(ModelType::Product)
        ->andReturn(['nif' => 'NIF']);

    $generateResourceConstants->shouldReceive('handle')
        ->once()
        ->with(ModelType::Price)
        ->andReturn(['monthly' => 'MONTHLY']);

    // Product write succeeds
    $writeConstantsToFile->shouldReceive('handle')
        ->once()
        ->withArgs(fn ($path) => str_ends_with($path, 'Models/BillingProduct.php'))
        ->andReturn(true);

    // Price write fails
    $writeConstantsToFile->shouldReceive('handle')
        ->once()
        ->withArgs(fn ($path) => str_ends_with($path, 'Models/BillingPrice.php'))
        ->andReturn(false);

    $service = new ConstantGeneratorService(
        $generateResourceConstants,
        $writeConstantsToFile
    );

    $result = $service->generate();

    expect($result)->toBeFalse();
});
