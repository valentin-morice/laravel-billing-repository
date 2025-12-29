<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product;

use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractProcessStage;

class ProcessProductChangesStage extends AbstractProcessStage
{
    public function __construct(
        protected ProductServiceInterface $productService,
    ) {}

    /**
     * Execute product sync operations
     */
    protected function process(DeployContext $context): void
    {
        // Process each product change by executing the sync
        $context->productChanges = $context->productChanges->map(function ($change) {
            $result = $this->productService->sync($change->productKey, $change->definition);

            // Update the change DTO with execution results
            return $change->withResult(
                type: $result->action,
                resultProduct: $result->product,
                changes: $result->changes
            );
        });
    }
}
