<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product;

use Illuminate\Database\QueryException;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class CreateAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    public function handle(string $productKey, ProductDefinition $definition): BillingProduct
    {
        $stripeProductId = $this->client->product()->create(
            $definition->name,
            $definition->description
        );

        try {
            return BillingProduct::create([
                'key' => $productKey,
                'provider_id' => $stripeProductId,
                'name' => $definition->name,
                'description' => $definition->description,
                'active' => true,
            ]);
        } catch (QueryException $e) {
            // Handle unique constraint violation (duplicate provider_id or key)
            if ($e->getCode() === '23000') {
                return BillingProduct::where('provider_id', $stripeProductId)
                    ->orWhere('key', $productKey)
                    ->firstOrFail();
            }

            throw $e;
        }
    }
}
