<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer;

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
    public function deploy(): ChangeSet
    {
        try {
            return $this->buildChangeSet->handle(dryRun: false);
        } catch (Throwable $e) {
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
}
