<?php

use Illuminate\Console\Command;
use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ProductChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatAnalysisAction;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatDeploymentProgressAction;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatSummaryAction;
use ValentinMorice\LaravelBillingRepository\Formatter\FormatterService;

afterEach(function () {
    m::close();
});

it('delegates formatAnalysis to FormatAnalysisAction', function () {
    $formatAnalysis = m::mock(FormatAnalysisAction::class);
    $formatDeploymentProgress = m::mock(FormatDeploymentProgressAction::class);
    $formatSummary = m::mock(FormatSummaryAction::class);

    $service = new FormatterService($formatAnalysis, $formatDeploymentProgress, $formatSummary);
    $command = m::mock(Command::class);
    $changeSet = new ChangeSet([], []);

    $formatAnalysis->shouldReceive('handle')
        ->once()
        ->with($command, $changeSet);

    $service->formatAnalysis($command, $changeSet);
});

it('delegates formatDeploymentProgress to FormatDeploymentProgressAction', function () {
    $formatAnalysis = m::mock(FormatAnalysisAction::class);
    $formatDeploymentProgress = m::mock(FormatDeploymentProgressAction::class);
    $formatSummary = m::mock(FormatSummaryAction::class);

    $service = new FormatterService($formatAnalysis, $formatDeploymentProgress, $formatSummary);
    $command = m::mock(Command::class);

    $change = new ProductChange(
        productKey: 'test',
        type: ChangeTypeEnum::Created,
        definition: null,
        existingProduct: null,
        resultProduct: null,
    );

    $formatDeploymentProgress->shouldReceive('handle')
        ->once()
        ->with($command, $change);

    $service->formatDeploymentProgress($command, $change);
});

it('delegates formatSummary to FormatSummaryAction', function () {
    $formatAnalysis = m::mock(FormatAnalysisAction::class);
    $formatDeploymentProgress = m::mock(FormatDeploymentProgressAction::class);
    $formatSummary = m::mock(FormatSummaryAction::class);

    $service = new FormatterService($formatAnalysis, $formatDeploymentProgress, $formatSummary);
    $command = m::mock(Command::class);
    $changeSet = new ChangeSet([], []);

    $formatSummary->shouldReceive('handle')
        ->once()
        ->with($command, $changeSet);

    $service->formatSummary($command, $changeSet);
});

it('can be resolved from container with all dependencies', function () {
    $service = app(FormatterService::class);

    expect($service)->toBeInstanceOf(FormatterService::class);

    $reflection = new ReflectionClass($service);

    $formatAnalysisProperty = $reflection->getProperty('formatAnalysis');
    $formatAnalysis = $formatAnalysisProperty->getValue($service);
    expect($formatAnalysis)->toBeInstanceOf(FormatAnalysisAction::class);

    $formatDeploymentProgressProperty = $reflection->getProperty('formatDeploymentProgress');
    $formatDeploymentProgress = $formatDeploymentProgressProperty->getValue($service);
    expect($formatDeploymentProgress)->toBeInstanceOf(FormatDeploymentProgressAction::class);

    $formatSummaryProperty = $reflection->getProperty('formatSummary');
    $formatSummary = $formatSummaryProperty->getValue($service);
    expect($formatSummary)->toBeInstanceOf(FormatSummaryAction::class);
});
