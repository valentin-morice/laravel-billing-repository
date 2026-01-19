<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Resource;

use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractProcessStage;

class ProcessArchivedResourcesStage extends AbstractProcessStage
{
    public function __construct(
        protected ProductServiceInterface $productService,
        protected PriceServiceInterface $priceService,
    ) {}

    /**
     * Execute archival operations for products and prices
     */
    protected function process(DeployContext $context): void
    {
        // Archive removed prices for each product
        foreach ($context->productChanges as $productChange) {
            if (! $productChange->resultProduct || ! $productChange->definition) {
                continue;
            }

            $configuredPriceKeys = array_keys($productChange->definition->prices);

            // Include newPriceKey values from duplicate strategy to prevent archiving just-created prices
            $duplicateKeys = $context->priceChanges
                ->filter(fn (PriceChange $change) => $change->productKey === $productChange->productKey && $change->newPriceKey !== null)
                ->map(fn (PriceChange $change) => $change->newPriceKey)
                ->all();

            $configuredPriceKeys = array_merge($configuredPriceKeys, $duplicateKeys);

            $result = $this->priceService->archiveRemoved(
                $productChange->resultProduct,
                $configuredPriceKeys
            );

            // Update existing price changes with archived status and add new ones
            foreach ($result->archivedPrices as $price) {
                // Check if this price is already in the changes (from detection)
                $existingChange = $context->priceChanges->first(function ($change) use ($price, $productChange) {
                    return $change->productKey === $productChange->productKey
                        && $change->priceKey === $price->key
                        && $change->type === ChangeTypeEnum::Archived;
                });

                if ($existingChange) {
                    // Update with result
                    $context->priceChanges = $context->priceChanges->map(function ($change) use ($existingChange, $price) {
                        if ($change === $existingChange) {
                            return $change->withResult(
                                type: ChangeTypeEnum::Archived,
                                resultPrice: $price,
                                changes: []
                            );
                        }

                        return $change;
                    });
                } else {
                    // Add new archived price change
                    $context->addPriceChange(new PriceChange(
                        productKey: $productChange->productKey,
                        priceKey: $price->key,
                        type: ChangeTypeEnum::Archived,
                        definition: null,
                        existingPrice: $price,
                        resultPrice: $price,
                        changes: [],
                    ));
                }
            }
        }

        // Archive removed products
        $configuredProductKeys = array_keys($context->definitions);
        $result = $this->productService->archiveRemoved($configuredProductKeys);

        // Update product changes with archived results
        foreach ($result->archivedProducts as $product) {
            $context->productChanges = $context->productChanges->map(function ($change) use ($product) {
                if ($change->productKey === $product->key && $change->type === ChangeTypeEnum::Archived) {
                    return $change->withResult(
                        type: ChangeTypeEnum::Archived,
                        resultProduct: $product,
                        changes: []
                    );
                }

                return $change;
            });
        }
    }
}
