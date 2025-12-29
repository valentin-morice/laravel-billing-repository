<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Resource;

use Closure;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ProductChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractPipelineStage;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class DetectArchivedResourcesStage extends AbstractPipelineStage
{
    /**
     * Detect products that exist in DB but not in config (should be archived)
     */
    public function handle(DeployContext $context, Closure $next): mixed
    {
        $configuredProductKeys = array_keys($context->definitions);

        $removedProducts = BillingProduct::where('active', true)
            ->whereNotIn('key', $configuredProductKeys)
            ->get();

        foreach ($removedProducts as $product) {
            $context->addProductChange(new ProductChange(
                productKey: $product->key,
                type: ChangeTypeEnum::Archived,
                definition: null,
                existingProduct: $product,
                resultProduct: null,
                changes: [],
            ));
        }

        return $next($context);
    }
}
