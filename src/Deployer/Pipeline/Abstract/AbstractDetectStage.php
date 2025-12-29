<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract;

use Closure;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;

/**
 * Base class for detection stages that analyze changes
 */
abstract class AbstractDetectStage extends AbstractPipelineStage
{
    public function __construct(
        protected DetectChangesAction $detectChanges,
    ) {}

    /**
     * Handle the pipeline stage
     */
    public function handle(DeployContext $context, Closure $next): mixed
    {
        $this->detect($context);

        return $next($context);
    }

    /**
     * Detect changes and add them to the context
     *
     * @param  DeployContext  $context  The deployment context
     */
    abstract protected function detect(DeployContext $context): void;
}
