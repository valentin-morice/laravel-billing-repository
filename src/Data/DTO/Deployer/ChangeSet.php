<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer;

use ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy;

readonly class ChangeSet
{
    /**
     * @param  array<ProductChange>  $productChanges
     * @param  array<PriceChange>  $priceChanges
     */
    public function __construct(
        public array $productChanges,
        public array $priceChanges,
    ) {}

    /**
     * Check if any price changes have immutable field changes
     */
    public function hasImmutableChanges(): bool
    {
        foreach ($this->priceChanges as $change) {
            if ($change->hasImmutableChanges) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get price changes that have immutable field changes
     *
     * @return array<PriceChange>
     */
    public function getImmutablePriceChanges(): array
    {
        return array_filter(
            $this->priceChanges,
            fn (PriceChange $change) => $change->hasImmutableChanges
        );
    }

    /**
     * Create a new ChangeSet with updated price changes
     *
     * @param  array<PriceChange>  $priceChanges
     */
    public function withPriceChanges(array $priceChanges): self
    {
        return new self(
            productChanges: $this->productChanges,
            priceChanges: $priceChanges,
        );
    }

    /**
     * Check if any price changes used the duplicate strategy
     */
    public function hasDuplicates(): bool
    {
        foreach ($this->priceChanges as $change) {
            if ($change->strategy === ImmutableFieldStrategy::Duplicate) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get summary counts by change type
     *
     * @return array{products: array<string, int>, prices: array<string, int>}
     */
    public function getSummary(): array
    {
        $summary = [
            'products' => ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'archived' => 0],
            'prices' => ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'archived' => 0],
        ];

        foreach ($this->productChanges as $change) {
            $summary['products'][$change->type->value]++;
        }

        foreach ($this->priceChanges as $change) {
            $summary['prices'][$change->type->value]++;
        }

        return $summary;
    }
}
