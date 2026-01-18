<?php

use ValentinMorice\LaravelBillingRepository\Data\Enum\ModelType;
use ValentinMorice\LaravelBillingRepository\EnumGenerator\Actions\GenerateEnumFileAction;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/billing-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);

    config(['billing.enums.path' => $this->tempDir]);
    config(['billing.enums.namespace' => 'App\\Enums\\Billing']);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $fileinfo->isDir() ? rmdir($fileinfo->getRealPath()) : unlink($fileinfo->getRealPath());
        }
        rmdir($this->tempDir);
    }
});

it('generates product key enum file', function () {
    $action = new GenerateEnumFileAction;
    $result = $action->handle(ModelType::Product, ['nif' => 'NIF', 'premium' => 'PREMIUM']);

    expect($result)->toBeTrue();

    $filePath = $this->tempDir.'/ProductKey.php';
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    expect($content)
        ->toContain('namespace App\\Enums\\Billing;')
        ->toContain('enum ProductKey: string')
        ->toContain("case NIF = 'nif';")
        ->toContain("case PREMIUM = 'premium';");
});

it('generates price key enum file', function () {
    $action = new GenerateEnumFileAction;
    $result = $action->handle(ModelType::Price, ['monthly' => 'MONTHLY', 'yearly' => 'YEARLY']);

    expect($result)->toBeTrue();

    $filePath = $this->tempDir.'/PriceKey.php';
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    expect($content)
        ->toContain('namespace App\\Enums\\Billing;')
        ->toContain('enum PriceKey: string')
        ->toContain("case MONTHLY = 'monthly';")
        ->toContain("case YEARLY = 'yearly';");
});

it('overwrites existing enum file', function () {
    $action = new GenerateEnumFileAction;

    // First generation
    $action->handle(ModelType::Product, ['old_key' => 'OLD_KEY']);

    // Second generation with different cases
    $result = $action->handle(ModelType::Product, ['new_key' => 'NEW_KEY']);

    expect($result)->toBeTrue();

    $filePath = $this->tempDir.'/ProductKey.php';
    $content = file_get_contents($filePath);

    expect($content)
        ->toContain("case NEW_KEY = 'new_key';")
        ->not->toContain("case OLD_KEY = 'old_key';");
});

it('handles empty cases array', function () {
    $action = new GenerateEnumFileAction;
    $result = $action->handle(ModelType::Product, []);

    expect($result)->toBeTrue();

    $filePath = $this->tempDir.'/ProductKey.php';
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    expect($content)
        ->toContain('enum ProductKey: string')
        ->toContain('{')
        ->toContain('}');
});
