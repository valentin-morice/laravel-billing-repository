<?php

namespace ValentinMorice\LaravelStripeRepository\Commands;

use Illuminate\Console\Command;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\StripeDeployer;

class StripeDeployCommand extends Command
{
    public $signature = 'stripe:deploy';

    public $description = 'Deploy Stripe products and prices from config to Stripe';

    public function __construct(
        protected StripeClientInterface $client
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Deploying Stripe products...');

        try {
            $deployer = new StripeDeployer($this->client);
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
