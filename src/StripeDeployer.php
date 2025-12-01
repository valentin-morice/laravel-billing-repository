<?php

namespace ValentinMorice\LaravelStripeRepository;

use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\ProductDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripePrice;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

class StripeDeployer
{
    public function __construct(
        protected StripeClientInterface $client
    ) {}

    public function deploy(): array
    {
        $results = [
            'products_created' => 0,
            'prices_created' => 0,
        ];

        $definitions = $this->getProductDefinitions();

        foreach ($definitions as $productKey => $definition) {
            $this->syncProduct($productKey, $definition, $results);
        }

        return $results;
    }

    protected function getProductDefinitions(): array
    {
        return config('stripe-repository.products', []);
    }

    protected function syncProduct(string $productKey, ProductDefinition $definition, array &$results): void
    {
        $existingProduct = StripeProduct::where('key', $productKey)->first();

        if ($existingProduct) {
            // For now, just use existing product
            $stripeProduct = $this->client->product()->retrieve($existingProduct->stripe_id);
        } else {
            $stripeProductId = $this->client->product()->create(
                $definition->name,
                $definition->description
            );

            $existingProduct = StripeProduct::create([
                'key' => $productKey,
                'stripe_id' => $stripeProductId,
                'name' => $definition->name,
                'active' => true,
            ]);

            $results['products_created']++;
        }

        // Sync prices for this product
        foreach ($definition->prices as $priceType => $priceDefinition) {
            $this->syncPrice($existingProduct, $priceType, $priceDefinition, $results);
        }
    }

    protected function syncPrice(
        StripeProduct $product,
        string $priceType,
        $priceDefinition,
        array &$results
    ): void {
        $existingPrice = StripePrice::where('product_id', $product->id)
            ->where('type', $priceType)
            ->where('amount', $priceDefinition->amount)
            ->where('active', true)
            ->first();

        if ($existingPrice) {
            // Price already exists with same amount, skip
            return;
        }

        $stripePriceId = $this->client->price()->create(
            $product->stripe_id,
            $priceDefinition->amount,
            $priceDefinition->currency,
            $priceDefinition->recurring
        );

        StripePrice::create([
            'product_id' => $product->id,
            'type' => $priceType,
            'stripe_id' => $stripePriceId,
            'amount' => $priceDefinition->amount,
            'currency' => $priceDefinition->currency,
            'recurring' => $priceDefinition->recurring,
            'active' => true,
        ]);

        $results['prices_created']++;
    }
}
