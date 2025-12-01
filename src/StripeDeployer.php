<?php

namespace ValentinMorice\LaravelStripeRepository;

use ValentinMorice\LaravelStripeRepository\Actions\Deployer\Price\SyncAction as SyncPriceAction;
use ValentinMorice\LaravelStripeRepository\Actions\Deployer\Product\SyncAction as SyncProductAction;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;

class StripeDeployer
{
    public function __construct(
        protected StripeClientInterface $client,
        protected ?SyncProductAction $syncProduct = null,
        protected ?SyncPriceAction $syncPrice = null,
    ) {
        $this->syncProduct ??= new SyncProductAction($client);
        $this->syncPrice ??= new SyncPriceAction($client);
    }

    public function deploy(): array
    {
        $results = [
            'products_created' => 0,
            'prices_created' => 0,
        ];

        $definitions = $this->getProductDefinitions();

        foreach ($definitions as $productKey => $definition) {
            $product = $this->syncProduct->handle($productKey, $definition);

            // Track if this was a new product
            if ($product->wasRecentlyCreated) {
                $results['products_created']++;
            }

            // Sync prices for this product
            foreach ($definition->prices as $priceType => $priceDefinition) {
                $price = $this->syncPrice->handle($product, $priceType, $priceDefinition);

                // Track if a new price was created (null means it was skipped)
                if ($price !== null) {
                    $results['prices_created']++;
                }
            }
        }

        return $results;
    }

    protected function getProductDefinitions(): array
    {
        return config('stripe-repository.products', []);
    }
}
