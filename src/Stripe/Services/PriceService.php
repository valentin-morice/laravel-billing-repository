<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Services;

use Illuminate\Database\Eloquent\Collection;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceArchiveResult;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceSyncResult;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price\ArchiveAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price\CreateAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\Abstract\AbstractResourceService;

/**
 * @extends AbstractResourceService<BillingPrice>
 */
class PriceService extends AbstractResourceService implements PriceServiceInterface
{
    public function __construct(
        protected ProviderClientInterface $client,
        protected DetectChangesAction $detectChanges,
        protected ?CreateAction $createAction = null,
        protected ?ArchiveAction $archiveAction = null,
    ) {
        parent::__construct($client, $detectChanges);

        $this->createAction ??= new CreateAction($client);
        $this->archiveAction ??= new ArchiveAction($client);
    }

    public function sync(BillingProduct $product, string $priceType, PriceDefinition $definition): PriceSyncResult
    {
        $existingPrice = BillingPrice::where('product_id', $product->id)
            ->where('type', $priceType)
            ->where('active', true)
            ->first();

        if ($existingPrice) {
            $changes = $this->detectChanges->handle($existingPrice, $definition, ['amount', 'currency', 'recurring', 'nickname']);

            if (! empty($changes)) {
                $oldPrice = $this->archiveAction->handle($existingPrice);
                $newPrice = $this->createAction->handle($product, $priceType, $definition);

                return PriceSyncResult::updated($newPrice, $oldPrice, $changes);
            }

            return PriceSyncResult::unchanged($existingPrice);
        }

        $price = $this->createAction->handle($product, $priceType, $definition);

        return PriceSyncResult::created($price);
    }

    public function archiveRemoved(BillingProduct $product, array $configuredPriceTypes): PriceArchiveResult
    {
        /** @var Collection<int, BillingPrice> $removedPrices */
        $removedPrices = $product->prices()
            ->where('active', true)
            ->whereNotIn('type', $configuredPriceTypes)
            ->get();

        $archivedPrices = $this->archiveRemovedResources($removedPrices);

        return PriceArchiveResult::fromArray($archivedPrices);
    }

    protected function getArchiveAction(): ArchiveAction
    {
        return $this->archiveAction;
    }
}
