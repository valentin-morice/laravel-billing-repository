<?php

namespace ValentinMorice\LaravelStripeRepository\Actions\Product;

use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

class ArchiveAction
{
    public function __construct(
        protected StripeClientInterface $client
    ) {}

    public function handle(StripeProduct $product): StripeProduct
    {
        $this->client->product()->archive($product->stripe_id);
        $product->update(['active' => false]);

        return $product->fresh();
    }
}
