<?php

namespace ValentinMorice\LaravelStripeRepository\Actions\Deployer\Price;

use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripePrice;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

class SyncAction
{
    public function __construct(
        protected StripeClientInterface $client
    ) {}

    public function handle(
        StripeProduct $product,
        string $priceType,
        PriceDefinition $priceDefinition
    ): ?StripePrice {
        // Check if price already exists with same amount
        $existingPrice = StripePrice::where('product_id', $product->id)
            ->where('type', $priceType)
            ->where('amount', $priceDefinition->amount)
            ->where('active', true)
            ->first();

        if ($existingPrice) {
            // Price already exists with same amount, skip
            return null;
        }

        // Create new price in Stripe
        $stripePriceId = $this->client->price()->create(
            $product->stripe_id,
            $priceDefinition->amount,
            $priceDefinition->currency,
            $priceDefinition->recurring
        );

        // Create record in database
        return StripePrice::create([
            'product_id' => $product->id,
            'type' => $priceType,
            'stripe_id' => $stripePriceId,
            'amount' => $priceDefinition->amount,
            'currency' => $priceDefinition->currency,
            'recurring' => $priceDefinition->recurring,
            'active' => true,
        ]);
    }
}
