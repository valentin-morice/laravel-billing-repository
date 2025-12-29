<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price;

use Illuminate\Database\QueryException;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
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

        try {
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
        } catch (QueryException $e) {
            // Handle unique constraint violation (duplicate provider_id)
            if ($e->getCode() === '23000') {
                return BillingPrice::where('provider_id', $stripePriceId)
                    ->firstOrFail();
            }

            throw $e;
        }
    }
}
