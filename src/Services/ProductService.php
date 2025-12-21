<?php

namespace ValentinMorice\LaravelStripeRepository\Services;

use Illuminate\Database\Eloquent\Collection;
use ValentinMorice\LaravelStripeRepository\Actions\Product\ArchiveAction;
use ValentinMorice\LaravelStripeRepository\Actions\Product\CreateAction;
use ValentinMorice\LaravelStripeRepository\Actions\Product\UpdateAction;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\ProductDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

class ProductService
{
    public function __construct(
        protected StripeClientInterface $client,
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
        $existingProduct = StripeProduct::where('key', $productKey)->first();

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
        /** @var Collection<int, StripeProduct> $removedProducts */
        $removedProducts = StripeProduct::where('active', true)
            ->whereNotIn('key', $configuredProductKeys)
            ->get();

        $archivedCount = 0;

        foreach ($removedProducts as $product) {
            $this->archiveAction->handle($product);
            $archivedCount++;
        }

        return $archivedCount;
    }

    protected function hasChanged(StripeProduct $product, ProductDefinition $definition): bool
    {
        return $product->name !== $definition->name
            || $product->description !== $definition->description;
    }
}
