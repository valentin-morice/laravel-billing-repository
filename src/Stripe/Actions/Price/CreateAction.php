<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class CreateAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    public function handle(BillingProduct $product, string $priceType, PriceDefinition $definition): BillingPrice
    {
        $stripePriceId = $this->client->price()->create(
            $product->provider_id,
            $definition->amount,
            $definition->currency,
            $definition->recurring,
            $definition->nickname
        );

        return BillingPrice::create([
            'product_id' => $product->id,
            'type' => $priceType,
            'provider_id' => $stripePriceId,
            'amount' => $definition->amount,
            'currency' => $definition->currency,
            'recurring' => $definition->recurring,
            'nickname' => $definition->nickname,
            'active' => true,
        ]);
    }
}
