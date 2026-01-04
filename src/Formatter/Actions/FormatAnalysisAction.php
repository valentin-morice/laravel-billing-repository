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
                    $old = $this->formatValue($diff['old']);
                    $new = $this->formatValue($diff['new']);
                    $command->line("    - {$field}: {$old} → {$new}");
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
                        $old = $this->formatValue($diff['old']);
                        $new = $this->formatValue($diff['new']);
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

    /**
     * Format a value for display (handles arrays, objects, scalars, null)
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return json_encode($value->toArray());
            } else {
                return json_encode($value);
            }
        }

        return (string) $value;
    }
}
