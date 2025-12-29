<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract;

use Closure;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;

/**
 * Base class for all pipeline stages
 */
abstract class AbstractPipelineStage
{
    /**
     * Handle the pipeline stage
     *
     * @param  DeployContext  $context  The deployment context
     * @param  Closure  $next  The next stage in the pipeline
     */
    abstract public function handle(DeployContext $context, Closure $next): mixed;
}
