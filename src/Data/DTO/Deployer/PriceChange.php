<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

readonly class PriceChange
{
    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        public string $productKey,
        public string $priceType,
        public ChangeTypeEnum $type,
        public ?PriceDefinition $definition,
        public ?BillingPrice $existingPrice,
        public ?BillingPrice $resultPrice,
        public array $changes = [],
    ) {}

    /**
     * Create a new instance with updated result values
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function withResult(
        ChangeTypeEnum $type,
        ?BillingPrice $resultPrice,
        array $changes = []
    ): self {
        return new self(
            productKey: $this->productKey,
            priceType: $this->priceType,
            type: $type,
            definition: $this->definition,
            existingPrice: $this->existingPrice,
            resultPrice: $resultPrice,
            changes: $changes,
        );
    }
}
