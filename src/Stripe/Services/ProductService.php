<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Services;

use Illuminate\Database\Eloquent\Collection;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Data\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product\ArchiveAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product\CreateAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product\UpdateAction;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        protected ProviderClientInterface $client,
        protected ?CreateAction $createAction = null,
        protected ?UpdateAction $updateAction = null,
        protected ?ArchiveAction $archiveAction = null,
    ) {
        $this->createAction ??= new CreateAction($client);
        $this->updateAction ??= new UpdateAction($client);
        $this->archiveAction ??= new ArchiveAction($client);
    }

    public function sync(string $productKey, ProductDefinition $definition): array
    {
        $existingProduct = BillingProduct::where('key', $productKey)->first();

        if ($existingProduct) {
            if ($this->hasChanged($existingProduct, $definition)) {
                $product = $this->updateAction->handle($existingProduct, $definition);

                return ['action' => 'updated', 'product' => $product];
            }

            return ['action' => 'unchanged', 'product' => $existingProduct];
        }

        $product = $this->createAction->handle($productKey, $definition);

        return ['action' => 'created', 'product' => $product];
    }

    public function archiveRemoved(array $configuredProductKeys): int
    {
        /** @var Collection<int, BillingProduct> $removedProducts */
        $removedProducts = BillingProduct::where('active', true)
            ->whereNotIn('key', $configuredProductKeys)
            ->get();

        $archivedCount = 0;

        foreach ($removedProducts as $product) {
            $this->archiveAction->handle($product);
            $archivedCount++;
        }

        return $archivedCount;
    }

    protected function hasChanged(BillingProduct $product, ProductDefinition $definition): bool
    {
        return $product->name !== $definition->name
            || $product->description !== $definition->description;
    }
}
