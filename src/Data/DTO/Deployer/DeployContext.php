<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer;

use Illuminate\Support\Collection;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;

class DeployContext
{
    /**
     * @param  array<string, ProductDefinition>  $definitions
     * @param  Collection<int, ProductChange>  $productChanges
     * @param  Collection<int, PriceChange>  $priceChanges
     */
    public function __construct(
        public readonly bool $isDryRun,
        public readonly array $definitions,
        public Collection $productChanges,
        public Collection $priceChanges,
    ) {}

    /**
     * Create a new context for deployment
     */
    public static function create(bool $isDryRun): self
    {
        $productsConfig = config('billing.products', []);

        $definitions = array_map(function ($productData) {
            return ProductDefinition::fromArray($productData);
        }, $productsConfig);

        return new self(
            isDryRun: $isDryRun,
            definitions: $definitions,
            productChanges: collect(),
            priceChanges: collect(),
        );
    }

    /**
     * Convert the context to a ChangeSet
     */
    public function toChangeSet(): ChangeSet
    {
        return new ChangeSet(
            $this->productChanges->all(),
            $this->priceChanges->all()
        );
    }

    /**
     * Add a product change to the collection
     */
    public function addProductChange(ProductChange $change): void
    {
        $this->productChanges->push($change);
    }

    /**
     * Add a price change to the collection
     */
    public function addPriceChange(PriceChange $change): void
    {
        $this->priceChanges->push($change);
    }
}
