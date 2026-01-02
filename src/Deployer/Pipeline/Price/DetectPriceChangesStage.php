<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price;

use Illuminate\Database\Eloquent\Collection;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractDetectStage;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

class DetectPriceChangesStage extends AbstractDetectStage
{
    /**
     * Detect price changes for all products
     */
    protected function detect(DeployContext $context): void
    {
        foreach ($context->productChanges as $productChange) {
            $productKey = $productChange->productKey;
            $definition = $productChange->definition;
            $existingProduct = $productChange->existingProduct;

            if (! $definition) {
                // No definition means product is being archived, skip prices
                continue;
            }

            // Analyze each price in the product definition
            foreach ($definition->prices as $priceType => $priceDefinition) {
                /** @var BillingPrice|null $existingPrice */
                $existingPrice = $existingProduct?->prices->firstWhere('type', $priceType);

                if ($existingPrice) {
                    $changes = $this->detectChanges->handle(
                        $existingPrice,
                        $priceDefinition,
                        ['amount', 'currency', 'recurring', 'nickname']
                    );

                    $type = empty($changes) ? ChangeTypeEnum::Unchanged : ChangeTypeEnum::Updated;

                    $context->addPriceChange(new PriceChange(
                        productKey: $productKey,
                        priceType: $priceType,
                        type: $type,
                        definition: $priceDefinition,
                        existingPrice: $existingPrice,
                        resultPrice: null,
                        changes: $changes,
                    ));
                } else {
                    $context->addPriceChange(new PriceChange(
                        productKey: $productKey,
                        priceType: $priceType,
                        type: ChangeTypeEnum::Created,
                        definition: $priceDefinition,
                        existingPrice: null,
                        resultPrice: null,
                        changes: [],
                    ));
                }
            }

            // Detect prices to be archived (exist in DB but not in config)
            if ($existingProduct) {
                $configuredPriceTypes = array_keys($definition->prices);
                /** @var Collection<int, BillingPrice> $removedPrices */
                $removedPrices = $existingProduct->prices->whereNotIn('type', $configuredPriceTypes);

                /** @var BillingPrice $price */
                foreach ($removedPrices as $price) {
                    $context->addPriceChange(new PriceChange(
                        productKey: $productKey,
                        priceType: $price->type,
                        type: ChangeTypeEnum::Archived,
                        definition: null,
                        existingPrice: $price,
                        resultPrice: null,
                        changes: [],
                    ));
                }
            }
        }
    }
}
