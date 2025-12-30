<?php

namespace ValentinMorice\LaravelBillingRepository\ConstantGenerator;

use ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions\GenerateResourceConstantsAction;
use ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions\WriteConstantsToFileAction;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ModelType;

class ConstantGeneratorService
{
    public function __construct(
        protected GenerateResourceConstantsAction $generateResourceConstants,
        protected WriteConstantsToFileAction $writeConstantsToFile,
    ) {}

    public function generate(): bool
    {
        $allSuccess = true;

        foreach (ModelType::cases() as $type) {
            $success = $this->generateForType($type);
            $allSuccess = $allSuccess && $success;
        }

        return $allSuccess;
    }

    private function generateForType(ModelType $type): bool
    {
        $constants = $this->generateResourceConstants->handle($type);

        $modelPath = match ($type) {
            ModelType::Product => __DIR__.'/../Models/BillingProduct.php',
            ModelType::Price => __DIR__.'/../Models/BillingPrice.php',
        };

        return $this->writeConstantsToFile->handle($modelPath, $constants);
    }
}
