<?php

namespace ValentinMorice\LaravelBillingRepository\Formatter\Actions;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\RecurringConfig;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;

class FormatAnalysisAction
{
    public function __construct(
        protected FormatSummaryAction $formatSummary,
    ) {}

    /**
     * Format and display the analysis output (preview of changes)
     */
    public function handle(Command $command, ChangeSet $changeSet): void
    {
        $command->info('Analyzing configuration...');
        $command->newLine();

        $command->line('<fg=green>Products:</>');
        foreach ($changeSet->productChanges as $change) {
            $symbol = $change->type->getSymbol();
            $name = match (true) {
                $change->definition !== null => $change->definition->name,
                $change->existingProduct !== null => $change->existingProduct->name,
                default => $change->productKey,
            };

            $command->line("  {$symbol} {$change->productKey} ({$name})");

            if ($change->type === ChangeTypeEnum::Created && $change->definition?->description) {
                $command->line("    \"{$change->definition->description}\"");
            }

            // Show diffs for updates
            if ($change->type === ChangeTypeEnum::Updated && ! empty($change->changes)) {
                foreach ($change->changes as $field => $diff) {
                    $old = $diff['old'] ?? 'null';
                    $new = $diff['new'] ?? 'null';
                    $command->line("    - {$field}: \"{$old}\" → \"{$new}\"");
                }
            }
        }

        $command->newLine();

        $command->line('<fg=green>Prices:</>');
        foreach ($changeSet->priceChanges as $change) {
            $symbol = $change->type->getSymbol();
            $fullType = "{$change->productKey}.{$change->priceType}";

            if ($change->definition) {
                $formattedPrice = $this->formatCurrency($change->definition->amount, $change->definition->currency);
                $recurring = $this->formatRecurring($change->definition->recurring);
                $command->line("  {$symbol} {$fullType} ({$formattedPrice}{$recurring})");
            } elseif ($change->existingPrice) {
                $formattedPrice = $this->formatCurrency($change->existingPrice->amount, $change->existingPrice->currency);
                $recurring = $this->formatRecurring($change->existingPrice->recurring);
                $command->line("  {$symbol} {$fullType} ({$formattedPrice}{$recurring})");
            } else {
                $command->line("  {$symbol} {$fullType}");
            }

            // Show diffs for updates
            if ($change->type === ChangeTypeEnum::Updated && ! empty($change->changes)) {
                foreach ($change->changes as $field => $diff) {
                    if ($field === 'amount') {
                        $currency = match (true) {
                            $change->definition !== null => $change->definition->currency,
                            $change->existingPrice !== null => $change->existingPrice->currency,
                            default => 'usd',
                        };
                        $old = $this->formatCurrency($diff['old'], $currency);
                        $new = $this->formatCurrency($diff['new'], $currency);
                    } else {
                        $old = is_array($diff['old']) ? json_encode($diff['old']) : ($diff['old'] ?? 'null');
                        $new = is_array($diff['new']) ? json_encode($diff['new']) : ($diff['new'] ?? 'null');
                    }
                    $command->line("    - {$field}: {$old} → {$new}");
                }
            }
        }

        $command->newLine();

        $this->formatSummary->handle($command, $changeSet);
    }

    /**
     * Format a price amount as currency
     */
    private function formatCurrency(int $amount, string $currency): string
    {
        $majorUnits = $amount / 100;

        return match (strtoupper($currency)) {
            'USD' => '$'.number_format($majorUnits, 2),
            'EUR' => '€'.number_format($majorUnits, 2),
            'GBP' => '£'.number_format($majorUnits, 2),
            default => strtoupper($currency).' '.number_format($majorUnits, 2),
        };
    }

    /**
     * Format recurring interval information
     */
    private function formatRecurring(RecurringConfig|array|null $recurring): string
    {
        if (! $recurring) {
            return '';
        }

        if ($recurring instanceof RecurringConfig) {
            $interval = $recurring->interval;
            $count = $recurring->intervalCount;
        } else {
            $interval = $recurring['interval'] ?? 'unknown';
            $count = $recurring['interval_count'] ?? 1;
        }

        if ($count === 1) {
            return "/{$interval}";
        }

        return "/every {$count} {$interval}s";
    }
}
