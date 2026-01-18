<?php

namespace ValentinMorice\LaravelBillingRepository\EnumGenerator\Actions;

use ValentinMorice\LaravelBillingRepository\Data\Enum\ModelType;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class GenerateResourceEnumsAction
{
    public function __construct(
        protected ConvertToEnumCaseNameAction $convertToEnumCaseName,
    ) {}

    /**
     * @return array<string, string>
     */
    public function handle(ModelType $type): array
    {
        $keys = match ($type) {
            ModelType::Product => BillingProduct::where('active', true)
                ->orderBy('key')
                ->pluck('key')
                ->all(),
            ModelType::Price => BillingPrice::where('active', true)
                ->distinct('key')
                ->orderBy('key')
                ->pluck('key')
                ->all(),
        };

        return $this->convertToEnumCaseName->handleMultiple($keys);
    }
}
