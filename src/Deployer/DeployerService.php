<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\BuildChangeSetAction;
use ValentinMorice\LaravelBillingRepository\Exceptions\Deployer\DeploymentFailedException;

class DeployerService
{
    public function __construct(
        protected BuildChangeSetAction $buildChangeSet,
    ) {}

    /**
     * Analyze what changes would be deployed (dry-run)
     */
    public function analyze(): ChangeSet
    {
        return $this->buildChangeSet->handle(dryRun: true);
    }

    /**
     * Deploy changes to the billing provider
     *
     * @throws DeploymentFailedException
     */
    public function deploy(?Command $command = null): ChangeSet
    {
        try {
            return $this->buildChangeSet->handle(dryRun: false, command: $command);
        } catch (Throwable $e) {
            $this->logAndThrowDeploymentFailed($e);
        }
    }

    /**
     * Deploy using a pre-analyzed ChangeSet with resolved strategies
     *
     * Use this when you have resolved immutable field change strategies
     * after calling analyze().
     *
     * @throws DeploymentFailedException
     */
    public function deployWithStrategies(ChangeSet $changeSet, ?Command $command = null): ChangeSet
    {
        try {
            return $this->buildChangeSet->handleWithStrategies($changeSet, command: $command);
        } catch (Throwable $e) {
            $this->logAndThrowDeploymentFailed($e);
        }
    }

    /**
     * Log deployment failure and throw exception
     *
     * @throws DeploymentFailedException
     *
     * @return never
     */
    private function logAndThrowDeploymentFailed(Throwable $e): never
    {
        $provider = ucfirst(config('billing.provider', 'your billing provider'));

        Log::error("Deployment failed - database may be out of sync with {$provider}", [
            'error' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'provider' => $provider,
        ]);

        throw new DeploymentFailedException(
            "Deployment failed. Your database may be out of sync with {$provider}. ".
            'Run "php artisan billing:deploy --dry-run" to see current state, '.
            'then re-run deployment to sync.',
            previous: $e
        );
    }
}
