<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

readonly class ProductChange
{
    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        public string $productKey,
        public ChangeTypeEnum $type,
        public ?ProductDefinition $definition,
        public ?BillingProduct $existingProduct,
        public ?BillingProduct $resultProduct,
        public array $changes = [],
    ) {}

    /**
     * Create a new instance with updated result values
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function withResult(
        ChangeTypeEnum $type,
        ?BillingProduct $resultProduct,
        array $changes = []
    ): self {
        return new self(
            productKey: $this->productKey,
            type: $type,
            definition: $this->definition,
            existingProduct: $this->existingProduct,
            resultProduct: $resultProduct,
            changes: $changes,
        );
    }
}
