<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer;

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
