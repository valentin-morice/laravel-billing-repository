<?php

namespace ValentinMorice\LaravelBillingRepository\Formatter\Actions;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy;

class FormatRequiredConfigChangesAction
{
    /**
     * Format and display config changes required for duplicate strategy
     */
    public function handle(Command $command, ChangeSet $changeSet): void
    {
        $duplicates = array_filter(
            $changeSet->priceChanges,
            fn (PriceChange $c) => $c->strategy === ImmutableFieldStrategy::Duplicate
        );

        if (empty($duplicates)) {
            return;
        }

        $command->newLine();
        $command->warn('Config update required after deployment:');
        $command->newLine();

        foreach ($duplicates as $change) {
            $this->formatDuplicateChange($command, $change);
        }
    }

    /**
     * Format a single duplicate change
     */
    private function formatDuplicateChange(Command $command, PriceChange $change): void
    {
        $command->line("  Add to config/billing.php under {$change->productKey}.prices:");
        $command->line("    <fg=green>'{$change->newPriceKey}'</> => [");

        if ($change->definition) {
            $command->line("        'amount' => {$change->definition->amount},");
            $command->line("        'currency' => '{$change->definition->currency}',");

            if ($change->definition->recurring) {
                $command->line("        'recurring' => [");
                $command->line("            'interval' => '{$change->definition->recurring->interval}',");
                $command->line("            'interval_count' => {$change->definition->recurring->intervalCount},");
                $command->line('        ],');
            }
        }

        $command->line('        // ... other fields');
        $command->line('    ],');
        $command->newLine();
    }
}
