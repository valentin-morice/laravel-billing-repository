<?php

namespace ValentinMorice\LaravelBillingRepository\Commands;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Deployer\DeployerService;
use ValentinMorice\LaravelBillingRepository\Exceptions\Deployer\DeploymentFailedException;
use ValentinMorice\LaravelBillingRepository\Formatter\FormatterService;

class DeployCommand extends Command
{
    public $signature = 'billing:deploy {--dry-run : Preview changes without executing}';

    public $description = 'Deploy billing products and prices from config to your provider';

    public function __construct(
        protected DeployerService $deployer,
        protected FormatterService $formatter,
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

            if ($this->option('dry-run')) {
                return self::SUCCESS;
            }

            $this->newLine();
            $this->info("Deploying to {$provider}...");

            $executedChangeSet = $this->deployer->deploy();

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
}
