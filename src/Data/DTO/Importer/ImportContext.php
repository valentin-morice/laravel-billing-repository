<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Importer;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class ImportContext
{
    /**
     * @param  bool  $shouldGenerateConfig  Whether to generate config file
     * @param  Collection<int, object>  $providerProducts  Provider product objects
     * @param  array<string, Collection<int, object>>  $providerPrices  Provider price objects grouped by product provider_id
     * @param  Collection<string, ImportedProduct>  $importedProducts  Results of imported products (keyed by provider_id)
     * @param  Collection<string, ImportedPrice>  $importedPrices  Results of imported prices (keyed by provider_id)
     */
    public function __construct(
        public readonly bool $shouldGenerateConfig,
        public Collection $providerProducts,
        public array $providerPrices,
        public Collection $importedProducts,
        public Collection $importedPrices,
        public readonly ?Command $command = null,
    ) {}

    public static function create(bool $shouldGenerateConfig, ?Command $command = null): self
    {
        return new self(
            shouldGenerateConfig: $shouldGenerateConfig,
            providerProducts: collect(),
            providerPrices: [],
            importedProducts: collect(),
            importedPrices: collect(),
            command: $command,
        );
    }

    public function addProduct(object $product): void
    {
        $this->providerProducts->push($product);
    }

    public function addPrice(string $productProviderId, object $price): void
    {
        if (! isset($this->providerPrices[$productProviderId])) {
            $this->providerPrices[$productProviderId] = collect();
        }

        $this->providerPrices[$productProviderId]->push($price);
    }

    public function recordProductImport(string $providerId, ImportedProduct $result): void
    {
        $this->importedProducts->put($providerId, $result);
    }

    public function recordPriceImport(string $providerId, ImportedPrice $result): void
    {
        $this->importedPrices->put($providerId, $result);
    }

    public function getImportedProduct(string $providerId): ?BillingProduct
    {
        return $this->importedProducts->get($providerId)?->product;
    }

    public function toImportResult(): ImportResult
    {
        return new ImportResult(
            $this->importedProducts->values()->all(),
            $this->importedPrices->values()->all()
        );
    }
}
