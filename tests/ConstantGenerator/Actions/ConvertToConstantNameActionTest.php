<?php

use ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions\ConvertToConstantNameAction;

it('converts snake_case to UPPER_CASE', function () {
    $action = new ConvertToConstantNameAction;

    expect($action->handle('nif'))->toBe('NIF')
        ->and($action->handle('social_sec'))->toBe('SOCIAL_SEC')
        ->and($action->handle('test_product'))->toBe('TEST_PRODUCT');
});

it('handles hyphens and spaces by converting to underscores', function () {
    $action = new ConvertToConstantNameAction;

    expect($action->handle('test-123'))->toBe('TEST_123')
        ->and($action->handle('test 123'))->toBe('TEST_123')
        ->and($action->handle('test.123'))->toBe('TEST_123');
});

it('handles numeric prefixes by adding underscore', function () {
    $action = new ConvertToConstantNameAction;

    expect($action->handle('123test'))->toBe('_123TEST')
        ->and($action->handle('3d_secure'))->toBe('_3D_SECURE');
});

it('handles reserved keywords by appending _CONST', function () {
    $action = new ConvertToConstantNameAction;

    expect($action->handle('class'))->toBe('CLASS_CONST')
        ->and($action->handle('function'))->toBe('FUNCTION_CONST')
        ->and($action->handle('const'))->toBe('CONST_CONST');
});

it('removes invalid characters', function () {
    $action = new ConvertToConstantNameAction;

    expect($action->handle('test@example'))->toBe('TESTEXAMPLE')
        ->and($action->handle('test#123!'))->toBe('TEST123');
});

it('handles multiple keys and returns array mapping', function () {
    $action = new ConvertToConstantNameAction;

    $result = $action->handleMultiple(['nif', 'social_sec', 'premium']);

    expect($result)->toBe([
        'nif' => 'NIF',
        'social_sec' => 'SOCIAL_SEC',
        'premium' => 'PREMIUM',
    ]);
});

it('handles collisions by appending numeric suffix', function () {
    $action = new ConvertToConstantNameAction;

    // Both 'nif' and 'NIF' would normalize to 'NIF'
    $result = $action->handleMultiple(['nif', 'NIF', 'Nif']);

    expect($result)->toBe([
        'nif' => 'NIF',
        'NIF' => 'NIF_2',
        'Nif' => 'NIF_3',
    ]);
});

it('handles empty string gracefully', function () {
    $action = new ConvertToConstantNameAction;

    expect($action->handle(''))->toBe('');
});

it('preserves underscores in valid names', function () {
    $action = new ConvertToConstantNameAction;

    expect($action->handle('my_constant_name'))->toBe('MY_CONSTANT_NAME');
});

it('handles consecutive separators', function () {
    $action = new ConvertToConstantNameAction;

    expect($action->handle('test---name'))->toBe('TEST_NAME')
        ->and($action->handle('test   name'))->toBe('TEST_NAME');
});
