<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Fetch;

use Throwable;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\Importer\Actions\FetchProductsFromProviderAction;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract\AbstractFetchStage;

class FetchProductsStage extends AbstractFetchStage
{
    public function __construct(
        protected FetchProductsFromProviderAction $fetchProducts,
    ) {}

    /**
     * @throws Throwable
     */
    protected function fetch(ImportContext $context): void
    {
        $products = $this->fetchProducts->handle();

        foreach ($products as $product) {
            $context->addProduct($product);
        }
    }
}
