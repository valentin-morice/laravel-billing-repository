<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract;

use Closure;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;

/**
 * Base class for processing stages that execute changes
 * Automatically skips execution in dry-run mode
 */
abstract class AbstractProcessStage extends AbstractPipelineStage
{
    /**
     * Handle the pipeline stage
     */
    public function handle(DeployContext $context, Closure $next): mixed
    {
        // Skip execution in dry-run mode
        if ($context->isDryRun) {
            return $next($context);
        }

        // Delegate to concrete implementation
        $this->process($context);

        return $next($context);
    }

    /**
     * Process the changes (only called when not in dry-run mode)
     *
     * @param  DeployContext  $context  The deployment context
     */
    abstract protected function process(DeployContext $context): void;
}
