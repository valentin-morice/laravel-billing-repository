<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Fetch;

use Throwable;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\Importer\Actions\FetchPricesForProductAction;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract\AbstractFetchStage;

class FetchPricesStage extends AbstractFetchStage
{
    public function __construct(
        protected FetchPricesForProductAction $fetchPrices,
    ) {}

    /**
     * @throws Throwable
     */
    protected function fetch(ImportContext $context): void
    {
        foreach ($context->providerProducts as $product) {
            $prices = $this->fetchPrices->handle($product->id);

            foreach ($prices as $price) {
                $context->addPrice($product->id, $price);
            }
        }
    }
}
