<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price;

use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractProcessStage;

class ProcessPriceChangesStage extends AbstractProcessStage
{
    public function __construct(
        protected PriceServiceInterface $priceService,
    ) {}

    /**
     * Execute price sync operations
     */
    protected function process(DeployContext $context): void
    {
        // Process each price change by executing the sync or archive
        $context->priceChanges = $context->priceChanges->map(function ($change) use ($context) {
            // Skip archived prices for now - they'll be handled in ProcessArchivedResourcesStage
            if ($change->type === ChangeTypeEnum::Archived) {
                return $change;
            }

            // Find the corresponding product result
            $productChange = $context->productChanges->firstWhere('productKey', $change->productKey);

            if (! $productChange || ! $productChange->resultProduct) {
                return $change;
            }

            $result = $this->priceService->sync(
                $productChange->resultProduct,
                $change->priceType,
                $change->definition
            );

            // Update the change DTO with execution results
            return $change->withResult(
                type: $result->action,
                resultPrice: $result->price,
                changes: $result->changes
            );
        });
    }
}
