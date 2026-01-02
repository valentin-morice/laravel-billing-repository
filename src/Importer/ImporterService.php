<?php

namespace ValentinMorice\LaravelBillingRepository\Importer;

use Illuminate\Pipeline\Pipeline;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportResult;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Fetch\FetchPricesStage;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Fetch\FetchProductsStage;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Persist\PersistPricesStage;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Persist\PersistProductsStage;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Post\GenerateConfigStage;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Post\GenerateConstantsStage;

class ImporterService
{
    public function __construct(
        protected Pipeline $pipeline,
    ) {}

    /**
     * Import products and prices from provider
     */
    public function import(bool $generateConfig = false): ImportResult
    {
        $context = ImportContext::create($generateConfig);

        /** @var ImportContext $result */
        $result = $this->pipeline
            ->send($context)
            ->through([
                FetchProductsStage::class,
                FetchPricesStage::class,
                PersistProductsStage::class,
                PersistPricesStage::class,
                GenerateConfigStage::class,
                GenerateConstantsStage::class,
            ])
            ->thenReturn();

        return $result->toImportResult();
    }
}
