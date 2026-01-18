<?php

namespace ValentinMorice\LaravelBillingRepository\Commands;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\ResolveImmutableStrategyAction;
use ValentinMorice\LaravelBillingRepository\Deployer\DeployerService;
use ValentinMorice\LaravelBillingRepository\Exceptions\Deployer\DeploymentCancelledException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Deployer\DeploymentFailedException;
use ValentinMorice\LaravelBillingRepository\Formatter\FormatterService;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class DeployCommand extends Command
{
    public $signature = 'billing:deploy {--dry-run : Preview changes without executing}';

    public $description = 'Deploy billing products and prices from config to your provider';

    public function __construct(
        protected DeployerService $deployer,
        protected FormatterService $formatter,
        protected ResolveImmutableStrategyAction $resolveImmutableStrategy,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! config('billing.api_key')) {
            $this->error('Billing API key is not configured.');
            $this->line('Please set BILLING_API_KEY in your .env file or publish the config.');

            return self::FAILURE;
        }

        try {
            $provider = ucfirst(config('billing.provider', 'stripe'));

            $changeSet = $this->deployer->analyze();
            $this->formatter->formatAnalysis($this, $changeSet);

            // Resolve strategies for immutable field changes
            if ($changeSet->hasImmutableChanges()) {
                $changeSet = $this->resolveImmutableStrategies($changeSet);
            }

            if ($this->option('dry-run')) {
                // Show config changes needed for duplicates
                if ($changeSet->hasDuplicates()) {
                    $this->formatter->formatRequiredConfigChanges($this, $changeSet);
                }

                return self::SUCCESS;
            }

            $this->newLine();
            $this->info("Deploying to {$provider}...");

            // Use deployWithStrategies if we have resolved strategies
            $executedChangeSet = $changeSet->hasImmutableChanges()
                ? $this->deployer->deployWithStrategies($changeSet)
                : $this->deployer->deploy();

            foreach ($executedChangeSet->productChanges as $change) {
                if ($change->type !== ChangeTypeEnum::Unchanged) {
                    $this->formatter->formatDeploymentProgress($this, $change);
                }
            }

            foreach ($executedChangeSet->priceChanges as $change) {
                if ($change->type !== ChangeTypeEnum::Unchanged) {
                    $this->formatter->formatDeploymentProgress($this, $change);
                }
            }

            $this->newLine();
            $this->info('Deployment complete!');

            // Regenerate config if any duplicates were created
            if ($executedChangeSet->hasDuplicates()) {
                $this->newLine();
                $this->call('billing:import', ['--generate-config' => true]);
                $this->info('Config file regenerated with new price keys.');
            }

            return self::SUCCESS;
        } catch (DeploymentCancelledException $e) {
            $this->newLine();
            $this->warn($e->getMessage());

            return self::SUCCESS;
        } catch (DeploymentFailedException $e) {
            $this->newLine();
            $this->error('⚠ DEPLOYMENT FAILED - OUT OF SYNC');
            $this->newLine();
            $this->warn($e->getMessage());
            $this->newLine();

            if ($this->output->isVerbose() && $e->getPrevious()) {
                $this->line('<fg=gray>Original error:</>');
                $this->line('<fg=gray>'.$e->getPrevious()->getMessage().'</>');
                $this->newLine();
            }

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('Deployment failed: '.$e->getMessage());

            if ($this->output->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Resolve strategies for all price changes with immutable field changes
     *
     * @throws DeploymentCancelledException
     */
    private function resolveImmutableStrategies(ChangeSet $changeSet): ChangeSet
    {
        $existingKeys = $this->collectExistingPriceKeys($changeSet);
        $updatedPriceChanges = [];

        foreach ($changeSet->priceChanges as $priceChange) {
            if ($priceChange->hasImmutableChanges) {
                $priceChange = $this->resolveImmutableStrategy->handle(
                    $this,
                    $priceChange,
                    $existingKeys
                );

                // Add new key to existing keys to prevent collisions
                if ($priceChange->newPriceKey !== null) {
                    $existingKeys[] = $priceChange->newPriceKey;
                }
            }

            $updatedPriceChanges[] = $priceChange;
        }

        return $changeSet->withPriceChanges($updatedPriceChanges);
    }

    /**
     * Collect all existing price keys from the change set and database
     *
     * @return array<string>
     */
    private function collectExistingPriceKeys(ChangeSet $changeSet): array
    {
        $keys = [];

        // Collect from change set
        foreach ($changeSet->priceChanges as $priceChange) {
            $keys[] = $priceChange->priceKey;
        }

        // Collect from database for all affected products
        $productKeys = array_unique(array_map(
            fn (PriceChange $c) => $c->productKey,
            $changeSet->priceChanges
        ));

        $productIds = BillingProduct::whereIn('key', $productKeys)
            ->where('active', true)
            ->pluck('id');

        $dbKeys = BillingPrice::whereIn('product_id', $productIds)
            ->where('active', true)
            ->pluck('key')
            ->all();

        return array_unique(array_merge($keys, $dbKeys));
    }

}
