<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price;

use Illuminate\Database\Eloquent\Collection;
use ValentinMorice\LaravelBillingRepository\Contracts\ImmutableFieldsInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\CreatePriceComparisonObjectAction;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractDetectStage;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

class DetectPriceChangesStage extends AbstractDetectStage
{
    /**
     * @param  class-string<ImmutableFieldsInterface>  $immutableFieldsClass
     */
    public function __construct(
        DetectChangesAction $detectChanges,
        protected CreatePriceComparisonObjectAction $createComparisonObject,
        protected string $immutableFieldsClass,
    ) {
        parent::__construct($detectChanges);
    }

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
            foreach ($definition->prices as $priceKey => $priceDefinition) {
                /** @var BillingPrice|null $existingPrice */
                $existingPrice = $existingProduct?->prices->firstWhere('key', $priceKey);

                if ($existingPrice) {
                    $existingForComparison = $this->createComparisonObject->handle($existingPrice);

                    $changes = $this->detectChanges->handle(
                        $existingForComparison,
                        $priceDefinition,
                        ['amount', 'currency', 'recurring', 'nickname', 'metadata', 'trialPeriodDays', 'stripe']
                    );

                    $type = empty($changes) ? ChangeTypeEnum::Unchanged : ChangeTypeEnum::Updated;
                    $immutableChanges = $this->immutableFieldsClass::filterImmutable($changes);
                    $hasImmutableChanges = ! empty($immutableChanges);

                    $context->addPriceChange(new PriceChange(
                        productKey: $productKey,
                        priceKey: $priceKey,
                        type: $type,
                        definition: $priceDefinition,
                        existingPrice: $existingPrice,
                        resultPrice: null,
                        changes: $changes,
                        hasImmutableChanges: $hasImmutableChanges,
                        immutableFieldsClass: $this->immutableFieldsClass,
                    ));
                } else {
                    $context->addPriceChange(new PriceChange(
                        productKey: $productKey,
                        priceKey: $priceKey,
                        type: ChangeTypeEnum::Created,
                        definition: $priceDefinition,
                        existingPrice: null,
                        resultPrice: null,
                        changes: [],
                        hasImmutableChanges: false,
                        immutableFieldsClass: $this->immutableFieldsClass,
                    ));
                }
            }

            // Detect prices to be archived (exist in DB but not in config)
            if ($existingProduct) {
                $configuredPriceKeys = array_keys($definition->prices);
                /** @var Collection<int, BillingPrice> $removedPrices */
                $removedPrices = $existingProduct->prices->whereNotIn('key', $configuredPriceKeys);

                /** @var BillingPrice $price */
                foreach ($removedPrices as $price) {
                    $context->addPriceChange(new PriceChange(
                        productKey: $productKey,
                        priceKey: $price->key,
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
