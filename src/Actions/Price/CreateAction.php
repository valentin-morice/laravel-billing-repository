<?php

namespace ValentinMorice\LaravelStripeRepository\Actions\Price;

use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripePrice;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

class CreateAction
{
    public function __construct(
        protected StripeClientInterface $client
    ) {}

    public function handle(StripeProduct $product, string $priceType, PriceDefinition $definition): StripePrice
    {
        $stripePriceId = $this->client->price()->create(
            $product->stripe_id,
            $definition->amount,
            $definition->currency,
            $definition->recurring,
            $definition->nickname
        );

        return StripePrice::create([
            'product_id' => $product->id,
            'type' => $priceType,
            'stripe_id' => $stripePriceId,
            'amount' => $definition->amount,
            'currency' => $definition->currency,
            'recurring' => $definition->recurring,
            'nickname' => $definition->nickname,
            'active' => true,
        ]);
    }
}
