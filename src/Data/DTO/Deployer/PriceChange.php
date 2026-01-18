<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer;

use ValentinMorice\LaravelBillingRepository\Contracts\ImmutableFieldsInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

readonly class PriceChange
{
    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  class-string<ImmutableFieldsInterface>|null  $immutableFieldsClass
     */
    public function __construct(
        public string $productKey,
        public string $priceKey,
        public ChangeTypeEnum $type,
        public ?PriceDefinition $definition,
        public ?BillingPrice $existingPrice,
        public ?BillingPrice $resultPrice,
        public array $changes = [],
        public bool $hasImmutableChanges = false,
        public ?ImmutableFieldStrategy $strategy = null,
        public ?string $newPriceKey = null,
        private ?string $immutableFieldsClass = null,
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
            priceKey: $this->priceKey,
            type: $type,
            definition: $this->definition,
            existingPrice: $this->existingPrice,
            resultPrice: $resultPrice,
            changes: $changes,
            hasImmutableChanges: $this->hasImmutableChanges,
            strategy: $this->strategy,
            newPriceKey: $this->newPriceKey,
            immutableFieldsClass: $this->immutableFieldsClass,
        );
    }

    /**
     * Create a new instance with the selected strategy
     */
    public function withStrategy(ImmutableFieldStrategy $strategy, ?string $newPriceKey = null): self
    {
        return new self(
            productKey: $this->productKey,
            priceKey: $this->priceKey,
            type: $this->type,
            definition: $this->definition,
            existingPrice: $this->existingPrice,
            resultPrice: $this->resultPrice,
            changes: $this->changes,
            hasImmutableChanges: $this->hasImmutableChanges,
            strategy: $strategy,
            newPriceKey: $newPriceKey,
            immutableFieldsClass: $this->immutableFieldsClass,
        );
    }

    /**
     * Get only the immutable field changes
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getImmutableChanges(): array
    {
        if ($this->immutableFieldsClass === null) {
            return [];
        }

        return $this->immutableFieldsClass::filterImmutable($this->changes);
    }

    /**
     * Get only the mutable field changes
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getMutableChanges(): array
    {
        if ($this->immutableFieldsClass === null) {
            return $this->changes;
        }

        return $this->immutableFieldsClass::filterMutable($this->changes);
    }

    /**
     * Get the price key to use for the new price
     *
     * Returns the newPriceKey if using duplicate strategy, otherwise the original priceKey
     */
    public function getEffectivePriceKey(): string
    {
        if ($this->strategy === ImmutableFieldStrategy::Duplicate && $this->newPriceKey !== null) {
            return $this->newPriceKey;
        }

        return $this->priceKey;
    }
}
