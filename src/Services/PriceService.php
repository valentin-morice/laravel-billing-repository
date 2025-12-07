<?php

namespace ValentinMorice\LaravelStripeRepository\Services;

use Illuminate\Database\Eloquent\Collection;
use ValentinMorice\LaravelStripeRepository\Actions\Deployer\Price\ArchiveAction;
use ValentinMorice\LaravelStripeRepository\Actions\Deployer\Price\CreateAction;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripePrice;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

class PriceService
{
    public function __construct(
        protected StripeClientInterface $client,
        protected ?CreateAction $createAction = null,
        protected ?ArchiveAction $archiveAction = null,
    ) {
        $this->createAction ??= new CreateAction($client);
        $this->archiveAction ??= new ArchiveAction($client);
    }

    public function sync(StripeProduct $product, string $priceType, PriceDefinition $definition): array
    {
        $existingPrice = StripePrice::where('product_id', $product->id)
            ->where('type', $priceType)
            ->where('active', true)
            ->first();

        if ($existingPrice) {
            if ($this->hasChanged($existingPrice, $definition)) {
                $oldPrice = $this->archiveAction->handle($existingPrice);
                $newPrice = $this->createAction->handle($product, $priceType, $definition);

                return ['action' => 'updated', 'old' => $oldPrice, 'new' => $newPrice];
            }

            return ['action' => 'unchanged', 'price' => $existingPrice];
        }

        $price = $this->createAction->handle($product, $priceType, $definition);

        return ['action' => 'created', 'price' => $price];
    }

    public function archiveRemoved(StripeProduct $product, array $configuredPriceTypes): int
    {
        /** @var Collection<int, StripePrice> $removedPrices */
        $removedPrices = $product->prices()
            ->where('active', true)
            ->whereNotIn('type', $configuredPriceTypes)
            ->get();

        $archivedCount = 0;

        foreach ($removedPrices as $price) {
            $this->archiveAction->handle($price);
            $archivedCount++;
        }

        return $archivedCount;
    }

    protected function hasChanged(StripePrice $price, PriceDefinition $definition): bool
    {
        return $price->amount !== $definition->amount
            || $price->currency !== $definition->currency
            || $price->recurring !== $definition->recurring
            || $price->nickname !== $definition->nickname;
    }
}
