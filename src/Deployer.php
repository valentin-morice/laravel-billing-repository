<?php

namespace ValentinMorice\LaravelBillingRepository;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\PriceService;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\ProductService;

class Deployer
{
    public function __construct(
        protected ProviderClientInterface $client,
        protected ?ProductService $productService = null,
        protected ?PriceService $priceService = null,
    ) {
        $this->productService ??= new ProductService($client);
        $this->priceService ??= new PriceService($client);
    }

    public function deploy(): array
    {
        $results = [
            'products' => [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'archived' => 0,
            ],
            'prices' => [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'archived' => 0,
            ],
        ];

        $definitions = $this->getProductDefinitions();

        foreach ($definitions as $productKey => $definition) {
            $productResult = $this->productService->sync($productKey, $definition);
            $product = $productResult['product'];
            $results['products'][$productResult['action']]++;

            // Track which price types are configured
            $configuredPriceTypes = array_keys($definition->prices);

            // Sync prices for this product
            foreach ($definition->prices as $priceType => $priceDefinition) {
                $priceResult = $this->priceService->sync($product, $priceType, $priceDefinition);
                $results['prices'][$priceResult['action']]++;
            }

            // Archive prices that were removed from config
            $archived = $this->priceService->archiveRemoved($product, $configuredPriceTypes);
            $results['prices']['archived'] += $archived;
        }

        // Archive products that were removed from config
        $configuredProductKeys = array_keys($definitions);
        $archivedProducts = $this->productService->archiveRemoved($configuredProductKeys);
        $results['products']['archived'] += $archivedProducts;

        return $results;
    }

    protected function getProductDefinitions(): array
    {
        return config('billing.products', []);
    }
}
