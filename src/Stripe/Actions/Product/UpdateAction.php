<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product;

use Illuminate\Support\Facades\DB;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripeProductFeatures;

class UpdateAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    public function handle(BillingProduct $product, ProductDefinition $definition): BillingProduct
    {
        return DB::transaction(function () use ($product, $definition) {
            $params = [];

            // Build params for changed universal fields
            if ($product->name !== $definition->name) {
                $params['name'] = $definition->name;
            }
            if ($product->description !== $definition->description) {
                $params['description'] = $definition->description;
            }
            if ($product->metadata !== $definition->metadata) {
                $params['metadata'] = $definition->metadata;
            }

            // Build params for changed Stripe fields
            $stripeFeatures = $product->stripe;
            if ($stripeFeatures?->tax_code !== $definition->stripe?->taxCode) {
                $params['tax_code'] = $definition->stripe?->taxCode;
            }
            if ($stripeFeatures?->statement_descriptor !== $definition->stripe?->statementDescriptor) {
                $params['statement_descriptor'] = $definition->stripe?->statementDescriptor;
            }

            // Call Stripe API if changes exist
            if (! empty($params)) {
                $this->client->product()->update($product->provider_id, $params);
            }

            // Update base model
            $product->update([
                'name' => $definition->name,
                'description' => $definition->description,
                'metadata' => $definition->metadata,
            ]);

            // Update or create Stripe features
            if ($definition->stripe !== null) {
                if ($stripeFeatures) {
                    $stripeFeatures->update([
                        'tax_code' => $definition->stripe->taxCode,
                        'statement_descriptor' => $definition->stripe->statementDescriptor,
                    ]);
                } else {
                    StripeProductFeatures::create([
                        'billing_product_id' => $product->id,
                        'tax_code' => $definition->stripe->taxCode,
                        'statement_descriptor' => $definition->stripe->statementDescriptor,
                    ]);
                }
            } elseif ($stripeFeatures) {
                // Delete stripe features if removed from config
                $stripeFeatures->delete();
            }

            return $product->fresh(['stripe']);
        });
    }
}
