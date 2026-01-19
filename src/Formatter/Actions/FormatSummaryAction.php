<?php

namespace ValentinMorice\LaravelBillingRepository\Formatter\Actions;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;

class FormatSummaryAction
{
    /**
     * Format and display the summary line
     */
    public function handle(Command $command, ChangeSet $changeSet): void
    {
        $summary = $changeSet->getSummary();

        $counts = [
            'created' => $summary['products']['created'] + $summary['prices']['created'],
            'updated' => $summary['products']['updated'] + $summary['prices']['updated'],
            'archived' => $summary['products']['archived'] + $summary['prices']['archived'],
            'unchanged' => $summary['products']['unchanged'] + $summary['prices']['unchanged'],
        ];

        $parts = collect($counts)
            ->filter(fn ($count) => $count > 0)
            ->map(fn ($count, $type) => "$count $type")
            ->values();

        if ($parts->isEmpty()) {
            $command->line('Summary: No changes');
        } else {
            $command->line('Summary: '.$parts->implode(', '));
        }

        $command->newLine();
    }
}
