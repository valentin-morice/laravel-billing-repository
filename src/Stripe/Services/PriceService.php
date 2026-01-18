<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;
use ValentinMorice\LaravelBillingRepository\Contracts\ImmutableFieldsInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderFeatureExtractorInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceArchiveResult;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceSyncResult;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\CreatePriceComparisonObjectAction;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price\ArchiveAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price\CreateAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price\UpdateAction;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\Abstract\AbstractResourceService;

/**
 * @extends AbstractResourceService<BillingPrice>
 */
class PriceService extends AbstractResourceService implements PriceServiceInterface
{
    /**
     * @param  class-string<ImmutableFieldsInterface>  $immutableFieldsClass
     */
    public function __construct(
        protected ProviderClientInterface $client,
        protected DetectChangesAction $detectChanges,
        protected ProviderFeatureExtractorInterface $featureExtractor,
        protected string $immutableFieldsClass,
        protected ?CreatePriceComparisonObjectAction $createComparisonObject = null,
        protected ?CreateAction $createAction = null,
        protected ?UpdateAction $updateAction = null,
        protected ?ArchiveAction $archiveAction = null,
    ) {
        parent::__construct($client, $detectChanges);

        $this->createComparisonObject ??= new CreatePriceComparisonObjectAction($featureExtractor);
        $this->createAction ??= new CreateAction($client);
        $this->updateAction ??= new UpdateAction($client);
        $this->archiveAction ??= new ArchiveAction($client);
    }

    /**
     * @throws Throwable
     */
    public function sync(
        BillingProduct $product,
        string $priceKey,
        PriceDefinition $definition,
        ?ImmutableFieldStrategy $strategy = null,
        ?string $newPriceKey = null
    ): PriceSyncResult {
        return DB::transaction(function () use ($product, $priceKey, $definition, $strategy, $newPriceKey) {
            $existingPrice = BillingPrice::where('product_id', $product->id)
                ->where('key', $priceKey)
                ->where('active', true)
                ->with('stripe')
                ->lockForUpdate()
                ->first();

            if ($existingPrice) {
                $existingForComparison = $this->createComparisonObject->handle($existingPrice);

                $changes = $this->detectChanges->handle(
                    $existingForComparison,
                    $definition,
                    ['amount', 'currency', 'recurring', 'nickname', 'metadata', 'trialPeriodDays', 'stripe']
                );

                if (! empty($changes)) {
                    $immutableChanges = $this->immutableFieldsClass::filterImmutable($changes);

                    if (! empty($immutableChanges)) {
                        return $this->handleImmutableChanges(
                            $product,
                            $priceKey,
                            $definition,
                            $existingPrice,
                            $changes,
                            $strategy,
                            $newPriceKey
                        );
                    }

                    $updatedPrice = $this->updateAction->handle($existingPrice, $definition);

                    return PriceSyncResult::updated($updatedPrice, null, $this->immutableFieldsClass::filterMutable($changes));
                }

                return PriceSyncResult::unchanged($existingPrice);
            }

            $price = $this->createAction->handle($product, $priceKey, $definition);

            return PriceSyncResult::created($price);
        });
    }

    /**
     * Handle immutable field changes based on strategy
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    private function handleImmutableChanges(
        BillingProduct $product,
        string $priceKey,
        PriceDefinition $definition,
        BillingPrice $existingPrice,
        array $changes,
        ?ImmutableFieldStrategy $strategy,
        ?string $newPriceKey
    ): PriceSyncResult {
        // Duplicate strategy: keep old price active, create new with different key
        if ($strategy === ImmutableFieldStrategy::Duplicate && $newPriceKey !== null) {
            $newPrice = $this->createAction->handle($product, $newPriceKey, $definition);

            return PriceSyncResult::duplicated($newPrice, $existingPrice, $changes);
        }

        // Archive strategy (default): archive old price, create new with same key
        $oldPrice = $this->archiveAction->handle($existingPrice);
        $newPrice = $this->createAction->handle($product, $priceKey, $definition);

        return PriceSyncResult::updated($newPrice, $oldPrice, $changes);
    }

    public function archiveRemoved(BillingProduct $product, array $configuredPriceKeys): PriceArchiveResult
    {
        /** @var Collection<int, BillingPrice> $removedPrices */
        $removedPrices = $product->prices()
            ->where('active', true)
            ->whereNotIn('key', $configuredPriceKeys)
            ->get();

        $archivedPrices = $this->archiveRemovedResources($removedPrices);

        return PriceArchiveResult::fromArray($archivedPrices);
    }

    protected function getArchiveAction(): ArchiveAction
    {
        return $this->archiveAction;
    }
}
