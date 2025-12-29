<?php

namespace ValentinMorice\LaravelBillingRepository\Formatter;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ProductChange;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatAnalysisAction;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatDeploymentProgressAction;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatSummaryAction;

class FormatterService
{
    public function __construct(
        protected FormatAnalysisAction $formatAnalysis,
        protected FormatDeploymentProgressAction $formatDeploymentProgress,
        protected FormatSummaryAction $formatSummary,
    ) {}

    /**
     * Format and display the analysis output (preview of changes)
     */
    public function formatAnalysis(Command $command, ChangeSet $changeSet): void
    {
        $this->formatAnalysis->handle($command, $changeSet);
    }

    /**
     * Format and display deployment progress for a single change
     */
    public function formatDeploymentProgress(Command $command, ProductChange|PriceChange $change): void
    {
        $this->formatDeploymentProgress->handle($command, $change);
    }

    /**
     * Format and display the summary line
     */
    public function formatSummary(Command $command, ChangeSet $changeSet): void
    {
        $this->formatSummary->handle($command, $changeSet);
    }
}
