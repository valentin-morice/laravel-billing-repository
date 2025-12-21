<?php

namespace ValentinMorice\LaravelBillingRepository\Commands;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Deployer;

class DeployCommand extends Command
{
    public $signature = 'billing:deploy';

    public $description = 'Deploy billing products and prices from config to your provider';

    public function __construct(
        protected ProviderClientInterface $client
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Deploying billing products...');

        try {
            $deployer = new Deployer($this->client);
            $results = $deployer->deploy();

            $this->newLine();
            $this->line('<fg=green>Products:</>');
            $this->info("  Created: {$results['products']['created']}");
            $this->info("  Updated: {$results['products']['updated']}");
            $this->info("  Unchanged: {$results['products']['unchanged']}");
            $this->info("  Archived: {$results['products']['archived']}");

            $this->newLine();
            $this->line('<fg=green>Prices:</>');
            $this->info("  Created: {$results['prices']['created']}");
            $this->info("  Updated: {$results['prices']['updated']}");
            $this->info("  Unchanged: {$results['prices']['unchanged']}");
            $this->info("  Archived: {$results['prices']['archived']}");

            $this->newLine();
            $this->info('Deployment complete!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Deployment failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
