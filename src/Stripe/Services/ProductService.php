<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Services;

use Illuminate\Database\Eloquent\Collection;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\ProductArchiveResult;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\ProductSyncResult;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product\ArchiveAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product\CreateAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product\UpdateAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\Abstract\AbstractResourceService;

/**
 * @extends AbstractResourceService<BillingProduct>
 */
class ProductService extends AbstractResourceService implements ProductServiceInterface
{
    public function __construct(
        protected ProviderClientInterface $client,
        protected DetectChangesAction $detectChanges,
        protected ?CreateAction $createAction = null,
        protected ?UpdateAction $updateAction = null,
        protected ?ArchiveAction $archiveAction = null,
    ) {
        parent::__construct($client, $detectChanges);

        $this->createAction ??= new CreateAction($client);
        $this->updateAction ??= new UpdateAction($client);
        $this->archiveAction ??= new ArchiveAction($client);
    }

    public function sync(string $productKey, ProductDefinition $definition): ProductSyncResult
    {
        $existingProduct = BillingProduct::where('key', $productKey)->first();

        if ($existingProduct) {
            $changes = $this->detectChanges->handle($existingProduct, $definition, ['name', 'description']);

            if (! empty($changes)) {
                $product = $this->updateAction->handle($existingProduct, $definition);

                return ProductSyncResult::updated($product, $changes);
            }

            return ProductSyncResult::unchanged($existingProduct);
        }

        $product = $this->createAction->handle($productKey, $definition);

        return ProductSyncResult::created($product);
    }

    public function archiveRemoved(array $configuredProductKeys): ProductArchiveResult
    {
        /** @var Collection<int, BillingProduct> $removedProducts */
        $removedProducts = BillingProduct::where('active', true)
            ->whereNotIn('key', $configuredProductKeys)
            ->get();

        $archivedProducts = $this->archiveRemovedResources($removedProducts);

        return ProductArchiveResult::fromArray($archivedProducts);
    }

    protected function getArchiveAction(): ArchiveAction
    {
        return $this->archiveAction;
    }
}
