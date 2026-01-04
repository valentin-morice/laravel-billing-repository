<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripeProductFeatures;

class CreateAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(string $productKey, ProductDefinition $definition): BillingProduct
    {
        return DB::transaction(function () use ($productKey, $definition) {
            // 1. Create in Stripe
            $stripeProductId = $this->client->product()->create(
                $definition->name,
                $definition->description,
                $definition->metadata,
                $definition->stripe?->taxCode,
                $definition->stripe?->statementDescriptor
            );

            try {
                $product = BillingProduct::create([
                    'key' => $productKey,
                    'provider_id' => $stripeProductId,
                    'name' => $definition->name,
                    'description' => $definition->description,
                    'metadata' => $definition->metadata,
                    'active' => true,
                ]);

                if ($definition->stripe !== null) {
                    StripeProductFeatures::create([
                        'billing_product_id' => $product->id,
                        'tax_code' => $definition->stripe->taxCode,
                        'statement_descriptor' => $definition->stripe->statementDescriptor,
                    ]);
                }

                return $product->fresh(['stripe']);
            } catch (QueryException $e) {
                // Handle unique constraint violation across all databases
                if (isset($e->errorInfo[0]) && str_starts_with($e->errorInfo[0], '23')) {
                    return BillingProduct::where('provider_id', $stripeProductId)
                        ->orWhere('key', $productKey)
                        ->with('stripe')
                        ->firstOrFail();
                }

                throw $e;
            }
        });
    }
}
