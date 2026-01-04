<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripePriceFeatures;

class CreateAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(BillingProduct $product, string $priceType, PriceDefinition $definition): BillingPrice
    {
        return DB::transaction(function () use ($product, $priceType, $definition) {
            $stripePriceId = $this->client->price()->create(
                $product->provider_id,
                $definition->amount,
                $definition->currency,
                $definition->recurring?->toArray(),
                $definition->nickname,
                $definition->metadata,
                $definition->trialPeriodDays,
                $definition->stripe?->taxBehavior,
                $definition->stripe?->lookupKey
            );

            try {
                $price = BillingPrice::create([
                    'product_id' => $product->id,
                    'type' => $priceType,
                    'provider_id' => $stripePriceId,
                    'amount' => $definition->amount,
                    'currency' => $definition->currency,
                    'recurring' => $definition->recurring?->toArray(),
                    'nickname' => $definition->nickname,
                    'metadata' => $definition->metadata,
                    'trial_period_days' => $definition->trialPeriodDays,
                    'active' => true,
                ]);

                if ($definition->stripe !== null) {
                    StripePriceFeatures::create([
                        'billing_price_id' => $price->id,
                        'tax_behavior' => $definition->stripe->taxBehavior,
                        'lookup_key' => $definition->stripe->lookupKey,
                    ]);
                }

                return $price->fresh(['stripe']);
            } catch (QueryException $e) {
                // Handle unique constraint violation across all databases
                if (isset($e->errorInfo[0]) && str_starts_with($e->errorInfo[0], '23')) {
                    return BillingPrice::where('provider_id', $stripePriceId)
                        ->with('stripe')
                        ->firstOrFail();
                }

                throw $e;
            }
        });
    }
}
