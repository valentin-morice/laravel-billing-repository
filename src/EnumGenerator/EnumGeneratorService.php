<?php

namespace ValentinMorice\LaravelBillingRepository\EnumGenerator;

use ValentinMorice\LaravelBillingRepository\Data\Enum\ModelType;
use ValentinMorice\LaravelBillingRepository\EnumGenerator\Actions\GenerateEnumFileAction;
use ValentinMorice\LaravelBillingRepository\EnumGenerator\Actions\GenerateResourceEnumsAction;

class EnumGeneratorService
{
    public function __construct(
        protected GenerateResourceEnumsAction $generateResourceEnums,
        protected GenerateEnumFileAction $generateEnumFile,
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
        $cases = $this->generateResourceEnums->handle($type);

        return $this->generateEnumFile->handle($type, $cases);
    }
}
