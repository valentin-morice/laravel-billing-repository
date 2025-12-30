<?php

namespace ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions;

use ValentinMorice\LaravelBillingRepository\Data\Enum\ModelType;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class GenerateResourceConstantsAction
{
    public function __construct(
        protected ConvertToConstantNameAction $convertToConstantName,
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
                ->distinct('type')
                ->orderBy('type')
                ->pluck('type')
                ->all(),
        };

        return $this->convertToConstantName->handleMultiple($keys);
    }
}
