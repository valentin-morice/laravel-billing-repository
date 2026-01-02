<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Importer;

readonly class ImportResult
{
    /**
     * @param  array<ImportedProduct>  $products
     * @param  array<ImportedPrice>  $prices
     */
    public function __construct(
        public array $products,
        public array $prices,
    ) {}

    public function getSummary(): array
    {
        $productStats = [
            'created' => 0,
            'updated' => 0,
            'total' => count($this->products),
        ];

        $priceStats = [
            'created' => 0,
            'updated' => 0,
            'total' => count($this->prices),
        ];

        foreach ($this->products as $imported) {
            $productStats[$imported->wasCreated ? 'created' : 'updated']++;
        }

        foreach ($this->prices as $imported) {
            $priceStats[$imported->wasCreated ? 'created' : 'updated']++;
        }

        return [
            'products' => $productStats,
            'prices' => $priceStats,
        ];
    }
}
