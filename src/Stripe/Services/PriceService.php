<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Services;

use Illuminate\Database\Eloquent\Collection;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price\ArchiveAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price\CreateAction;

class PriceService
{
    public function __construct(
        protected ProviderClientInterface $client,
        protected ?CreateAction $createAction = null,
        protected ?ArchiveAction $archiveAction = null,
    ) {
        $this->createAction ??= new CreateAction($client);
        $this->archiveAction ??= new ArchiveAction($client);
    }

    public function sync(BillingProduct $product, string $priceType, PriceDefinition $definition): array
    {
        $existingPrice = BillingPrice::where('product_id', $product->id)
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

    public function archiveRemoved(BillingProduct $product, array $configuredPriceTypes): int
    {
        /** @var Collection<int, BillingPrice> $removedPrices */
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

    protected function hasChanged(BillingPrice $price, PriceDefinition $definition): bool
    {
        return $price->amount !== $definition->amount
            || $price->currency !== $definition->currency
            || $price->recurring !== $definition->recurring
            || $price->nickname !== $definition->nickname;
    }
}
