<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ProductChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\CreateProductComparisonObjectAction;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractDetectStage;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class DetectProductChangesStage extends AbstractDetectStage
{
    public function __construct(
        DetectChangesAction $detectChanges,
        protected CreateProductComparisonObjectAction $createComparisonObject,
    ) {
        parent::__construct($detectChanges);
    }

    /**
     * Detect product changes by comparing config definitions with existing products
     */
    protected function detect(DeployContext $context): void
    {
        $productKeys = array_keys($context->definitions);
        $existingProducts = BillingProduct::whereIn('key', $productKeys)
            ->with(['prices' => function ($query) {
                $query->where('active', true)->with('stripe');
            }, 'stripe'])
            ->get()
            ->keyBy('key');

        foreach ($context->definitions as $productKey => $definition) {
            /** @var BillingProduct|null $existingProduct */
            $existingProduct = $existingProducts->get($productKey);

            if ($existingProduct) {
                $existingForComparison = $this->createComparisonObject->handle($existingProduct);

                $changes = $this->detectChanges->handle(
                    $existingForComparison,
                    $definition,
                    ['name', 'description', 'metadata', 'stripe']
                );

                $type = empty($changes) ? ChangeTypeEnum::Unchanged : ChangeTypeEnum::Updated;

                $context->addProductChange(new ProductChange(
                    productKey: $productKey,
                    type: $type,
                    definition: $definition,
                    existingProduct: $existingProduct,
                    resultProduct: null,
                    changes: $changes,
                ));
            } else {
                $context->addProductChange(new ProductChange(
                    productKey: $productKey,
                    type: ChangeTypeEnum::Created,
                    definition: $definition,
                    existingProduct: null,
                    resultProduct: null,
                    changes: [],
                ));
            }
        }
    }
}
