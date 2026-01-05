<?php

use ValentinMorice\LaravelBillingRepository\Data\Enum\ModelType;
use ValentinMorice\LaravelBillingRepository\EnumGenerator\Actions\GenerateEnumFileAction;
use ValentinMorice\LaravelBillingRepository\EnumGenerator\Actions\GenerateResourceEnumsAction;
use ValentinMorice\LaravelBillingRepository\EnumGenerator\EnumGeneratorService;

it('generates enums for both products and prices', function () {
    $generateResourceEnums = Mockery::mock(GenerateResourceEnumsAction::class);
    $generateEnumFile = Mockery::mock(GenerateEnumFileAction::class);

    $generateResourceEnums->shouldReceive('handle')
        ->once()
        ->with(ModelType::Product)
        ->andReturn(['nif' => 'NIF']);

    $generateResourceEnums->shouldReceive('handle')
        ->once()
        ->with(ModelType::Price)
        ->andReturn(['monthly' => 'MONTHLY']);

    $generateEnumFile->shouldReceive('handle')
        ->with(ModelType::Product, ['nif' => 'NIF'])
        ->once()
        ->andReturn(true);

    $generateEnumFile->shouldReceive('handle')
        ->with(ModelType::Price, ['monthly' => 'MONTHLY'])
        ->once()
        ->andReturn(true);

    $service = new EnumGeneratorService(
        $generateResourceEnums,
        $generateEnumFile
    );

    $result = $service->generate();

    expect($result)->toBeTrue();
});

it('returns false if product generation fails', function () {
    $generateResourceEnums = Mockery::mock(GenerateResourceEnumsAction::class);
    $generateEnumFile = Mockery::mock(GenerateEnumFileAction::class);

    $generateResourceEnums->shouldReceive('handle')
        ->once()
        ->with(ModelType::Product)
        ->andReturn(['nif' => 'NIF']);

    $generateResourceEnums->shouldReceive('handle')
        ->once()
        ->with(ModelType::Price)
        ->andReturn(['monthly' => 'MONTHLY']);

    // Product enum generation fails
    $generateEnumFile->shouldReceive('handle')
        ->with(ModelType::Product, ['nif' => 'NIF'])
        ->once()
        ->andReturn(false);

    // Price enum generation succeeds
    $generateEnumFile->shouldReceive('handle')
        ->with(ModelType::Price, ['monthly' => 'MONTHLY'])
        ->once()
        ->andReturn(true);

    $service = new EnumGeneratorService(
        $generateResourceEnums,
        $generateEnumFile
    );

    $result = $service->generate();

    expect($result)->toBeFalse();
});

it('returns false if price generation fails', function () {
    $generateResourceEnums = Mockery::mock(GenerateResourceEnumsAction::class);
    $generateEnumFile = Mockery::mock(GenerateEnumFileAction::class);

    $generateResourceEnums->shouldReceive('handle')
        ->once()
        ->with(ModelType::Product)
        ->andReturn(['nif' => 'NIF']);

    $generateResourceEnums->shouldReceive('handle')
        ->once()
        ->with(ModelType::Price)
        ->andReturn(['monthly' => 'MONTHLY']);

    // Product enum generation succeeds
    $generateEnumFile->shouldReceive('handle')
        ->with(ModelType::Product, ['nif' => 'NIF'])
        ->once()
        ->andReturn(true);

    // Price enum generation fails
    $generateEnumFile->shouldReceive('handle')
        ->with(ModelType::Price, ['monthly' => 'MONTHLY'])
        ->once()
        ->andReturn(false);

    $service = new EnumGeneratorService(
        $generateResourceEnums,
        $generateEnumFile
    );

    $result = $service->generate();

    expect($result)->toBeFalse();
});
