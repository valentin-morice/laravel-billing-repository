<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Actions;

use Illuminate\Pipeline\Pipeline;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Post\GenerateEnumsStage;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price\DetectPriceChangesStage;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price\ProcessPriceChangesStage;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product\DetectProductChangesStage;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Product\ProcessProductChangesStage;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Resource\DetectArchivedResourcesStage;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Resource\ProcessArchivedResourcesStage;

class BuildChangeSetAction
{
    public function __construct(
        protected Pipeline $pipeline,
    ) {}

    /**
     * Build a change set by analyzing or executing deployment
     */
    public function handle(bool $dryRun): ChangeSet
    {
        $context = DeployContext::create($dryRun);

        /** @var DeployContext $result */
        $result = $this->pipeline
            ->send($context)
            ->through([
                DetectProductChangesStage::class,
                ProcessProductChangesStage::class,
                DetectPriceChangesStage::class,
                ProcessPriceChangesStage::class,
                DetectArchivedResourcesStage::class,
                ProcessArchivedResourcesStage::class,
                GenerateEnumsStage::class,
            ])
            ->thenReturn();

        return $result->toChangeSet();
    }

    /**
     * Deploy using a pre-analyzed ChangeSet with resolved strategies
     *
     * This skips detection stages since we already have the detected changes
     * with user-resolved strategies for immutable field changes.
     */
    public function handleWithStrategies(ChangeSet $changeSet): ChangeSet
    {
        $context = DeployContext::createFromChangeSet($changeSet);

        /** @var DeployContext $result */
        $result = $this->pipeline
            ->send($context)
            ->through([
                // Skip detection stages - changes already detected with strategies
                ProcessProductChangesStage::class,
                ProcessPriceChangesStage::class,
                DetectArchivedResourcesStage::class,
                ProcessArchivedResourcesStage::class,
                GenerateEnumsStage::class,
            ])
            ->thenReturn();

        return $result->toChangeSet();
    }
}
