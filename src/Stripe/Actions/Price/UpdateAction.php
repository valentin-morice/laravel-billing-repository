<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price;

use Illuminate\Support\Facades\DB;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripePriceFeatures;

class UpdateAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(BillingPrice $price, PriceDefinition $definition): BillingPrice
    {
        return DB::transaction(function () use ($price, $definition) {
            $params = [];

            if ($price->nickname !== $definition->nickname) {
                $params['nickname'] = $definition->nickname;
            }
            if ($price->metadata !== $definition->metadata) {
                $params['metadata'] = $definition->metadata;
            }

            $stripeFeatures = $price->stripe;
            if ($stripeFeatures?->tax_behavior !== $definition->stripe?->taxBehavior) {
                $params['tax_behavior'] = $definition->stripe?->taxBehavior;
            }
            if ($stripeFeatures?->lookup_key !== $definition->stripe?->lookupKey) {
                $params['lookup_key'] = $definition->stripe?->lookupKey;
            }

            if (! empty($params)) {
                $this->client->price()->update($price->provider_id, $params);
            }

            $price->update([
                'nickname' => $definition->nickname,
                'metadata' => $definition->metadata,
            ]);

            if ($definition->stripe !== null) {
                if ($stripeFeatures) {
                    $stripeFeatures->update([
                        'tax_behavior' => $definition->stripe->taxBehavior,
                        'lookup_key' => $definition->stripe->lookupKey,
                    ]);
                } else {
                    StripePriceFeatures::create([
                        'billing_price_id' => $price->id,
                        'tax_behavior' => $definition->stripe->taxBehavior,
                        'lookup_key' => $definition->stripe->lookupKey,
                    ]);
                }
            } elseif ($stripeFeatures) {
                $stripeFeatures->delete();
            }

            return $price->fresh(['stripe']);
        });
    }
}
