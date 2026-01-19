<?php

namespace ValentinMorice\LaravelBillingRepository\Commands;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportResult;
use ValentinMorice\LaravelBillingRepository\Importer\ImporterService;

class ImportCommand extends Command
{
    public $signature = 'billing:import
        {--db-only : Import to database only (no config generation)}
        {--generate-config : Import and generate config file}
        {--quiet : Suppress output (useful when called from other commands)}';

    public $description = 'Import products and prices from your billing provider';

    public function __construct(
        protected ImporterService $importer,
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

        $generateConfig = $this->option('generate-config');

        if (! $this->option('db-only') && ! $generateConfig) {
            $this->warn('No mode specified, defaulting to --db-only');
            $generateConfig = false;
        }

        try {
            $quiet = $this->option('quiet');
            $provider = ucfirst(config('billing.provider', 'stripe'));

            if (! $quiet) {
                $this->info("Importing from {$provider}...");
                $this->newLine();
            }

            $result = $this->importer->import($generateConfig, $quiet ? null : $this);

            if (! $quiet) {
                $this->displayResults($result, $generateConfig);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");

            if ($this->output->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    protected function displayResults(ImportResult $result, bool $configGenerated): void
    {
        $summary = $result->getSummary();

        $this->newLine();
        $this->info('Import completed successfully!');
        $this->newLine();

        // Products summary
        $this->line('Products:');
        $this->line("  Created: {$summary['products']['created']}");
        $this->line("  Updated: {$summary['products']['updated']}");
        $this->line("  Total: {$summary['products']['total']}");
        $this->newLine();

        // Prices summary
        $this->line('Prices:');
        $this->line("  Created: {$summary['prices']['created']}");
        $this->line("  Updated: {$summary['prices']['updated']}");
        $this->line("  Total: {$summary['prices']['total']}");
        $this->newLine();

        if ($configGenerated) {
            $configPath = config_path('billing.php');
            $this->info("Config file updated: {$configPath}");
            $this->line('The products array has been populated from your Stripe account.');
            $this->line('You can now deploy with: php artisan billing:deploy');
        }
    }
}
