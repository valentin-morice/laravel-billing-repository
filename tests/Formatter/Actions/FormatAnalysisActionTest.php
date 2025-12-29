<?php

use Illuminate\Console\Command;
use Mockery as m;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ProductChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatAnalysisAction;
use ValentinMorice\LaravelBillingRepository\Formatter\Actions\FormatSummaryAction;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

afterEach(function () {
    m::close();
});

it('formats analysis header and sections', function () {
    $formatSummary = m::mock(FormatSummaryAction::class);
    $action = new FormatAnalysisAction($formatSummary);
    $command = m::mock(Command::class);

    $changeSet = new ChangeSet([], []);

    $command->shouldReceive('info')
        ->once()
        ->with('Analyzing configuration...');

    $command->shouldReceive('newLine')
        ->times(3);

    $command->shouldReceive('line')
        ->with('<fg=green>Products:</>');

    $command->shouldReceive('line')
        ->with('<fg=green>Prices:</>');

    $formatSummary->shouldReceive('handle')
        ->once()
        ->with($command, $changeSet);

    $action->handle($command, $changeSet);
});

it('formats created product with description', function () {
    $formatSummary = m::mock(FormatSummaryAction::class);
    $action = new FormatAnalysisAction($formatSummary);
    $command = m::mock(Command::class);

    $definition = new ProductDefinition(
        name: 'Premium Plan',
        prices: [],
        description: 'Premium subscription tier',
    );

    $productChange = new ProductChange(
        productKey: 'premium-plan',
        type: ChangeTypeEnum::Created,
        definition: $definition,
        existingProduct: null,
        resultProduct: null,
    );

    $changeSet = new ChangeSet([$productChange], []);

    $command->shouldReceive('info')->once();
    $command->shouldReceive('newLine')->times(3);
    $command->shouldReceive('line')->with('<fg=green>Products:</>');
    $command->shouldReceive('line')->with('<fg=green>Prices:</>');

    $command->shouldReceive('line')
        ->once()
        ->with('  + premium-plan (Premium Plan)');

    $command->shouldReceive('line')
        ->once()
        ->with('    "Premium subscription tier"');

    $formatSummary->shouldReceive('handle')->once();

    $action->handle($command, $changeSet);
});

it('formats updated product with diffs', function () {
    $formatSummary = m::mock(FormatSummaryAction::class);
    $action = new FormatAnalysisAction($formatSummary);
    $command = m::mock(Command::class);

    $existingProduct = new BillingProduct(['name' => 'Basic Plan', 'description' => 'Basic tier']);
    $definition = new ProductDefinition(name: 'Starter Plan', prices: [], description: 'Starter tier');

    $productChange = new ProductChange(
        productKey: 'basic-plan',
        type: ChangeTypeEnum::Updated,
        definition: $definition,
        existingProduct: $existingProduct,
        resultProduct: null,
        changes: [
            'name' => ['old' => 'Basic Plan', 'new' => 'Starter Plan'],
            'description' => ['old' => 'Basic tier', 'new' => 'Starter tier'],
        ],
    );

    $changeSet = new ChangeSet([$productChange], []);

    $command->shouldReceive('info')->once();
    $command->shouldReceive('newLine')->times(3);
    $command->shouldReceive('line')->with('<fg=green>Products:</>');
    $command->shouldReceive('line')->with('<fg=green>Prices:</>');

    $command->shouldReceive('line')
        ->once()
        ->with('  ~ basic-plan (Starter Plan)');

    $command->shouldReceive('line')
        ->once()
        ->with('    - name: "Basic Plan" → "Starter Plan"');

    $command->shouldReceive('line')
        ->once()
        ->with('    - description: "Basic tier" → "Starter tier"');

    $formatSummary->shouldReceive('handle')->once();

    $action->handle($command, $changeSet);
});

it('formats archived product', function () {
    $formatSummary = m::mock(FormatSummaryAction::class);
    $action = new FormatAnalysisAction($formatSummary);
    $command = m::mock(Command::class);

    $existingProduct = new BillingProduct(['name' => 'Legacy Plan', 'key' => 'legacy-plan']);

    $productChange = new ProductChange(
        productKey: 'legacy-plan',
        type: ChangeTypeEnum::Archived,
        definition: null,
        existingProduct: $existingProduct,
        resultProduct: null,
    );

    $changeSet = new ChangeSet([$productChange], []);

    $command->shouldReceive('info')->once();
    $command->shouldReceive('newLine')->times(3);
    $command->shouldReceive('line')->with('<fg=green>Products:</>');
    $command->shouldReceive('line')->with('<fg=green>Prices:</>');

    $command->shouldReceive('line')
        ->once()
        ->with('  - legacy-plan (Legacy Plan)');

    $formatSummary->shouldReceive('handle')->once();

    $action->handle($command, $changeSet);
});

it('formats created price with currency and recurring', function () {
    $formatSummary = m::mock(FormatSummaryAction::class);
    $action = new FormatAnalysisAction($formatSummary);
    $command = m::mock(Command::class);

    $definition = new PriceDefinition(
        amount: 2999,
        currency: 'usd',
        recurring: ['interval' => 'month', 'interval_count' => 1],
    );

    $priceChange = new PriceChange(
        productKey: 'premium-plan',
        priceType: 'monthly',
        type: ChangeTypeEnum::Created,
        definition: $definition,
        existingPrice: null,
        resultPrice: null,
    );

    $changeSet = new ChangeSet([], [$priceChange]);

    $command->shouldReceive('info')->once();
    $command->shouldReceive('newLine')->times(3);
    $command->shouldReceive('line')->with('<fg=green>Products:</>');
    $command->shouldReceive('line')->with('<fg=green>Prices:</>');

    $command->shouldReceive('line')
        ->once()
        ->with('  + premium-plan.monthly ($29.99/month)');

    $formatSummary->shouldReceive('handle')->once();

    $action->handle($command, $changeSet);
});

it('formats updated price with amount diff', function () {
    $formatSummary = m::mock(FormatSummaryAction::class);
    $action = new FormatAnalysisAction($formatSummary);
    $command = m::mock(Command::class);

    $existingPrice = new BillingPrice([
        'amount' => 999,
        'currency' => 'usd',
        'recurring' => ['interval' => 'month'],
    ]);

    $definition = new PriceDefinition(
        amount: 1999,
        currency: 'usd',
        recurring: ['interval' => 'month'],
    );

    $priceChange = new PriceChange(
        productKey: 'basic-plan',
        priceType: 'monthly',
        type: ChangeTypeEnum::Updated,
        definition: $definition,
        existingPrice: $existingPrice,
        resultPrice: null,
        changes: [
            'amount' => ['old' => 999, 'new' => 1999],
        ],
    );

    $changeSet = new ChangeSet([], [$priceChange]);

    $command->shouldReceive('info')->once();
    $command->shouldReceive('newLine')->times(3);
    $command->shouldReceive('line')->with('<fg=green>Products:</>');
    $command->shouldReceive('line')->with('<fg=green>Prices:</>');

    $command->shouldReceive('line')
        ->once()
        ->with('  ~ basic-plan.monthly ($19.99/month)');

    $command->shouldReceive('line')
        ->once()
        ->with('    - amount: $9.99 → $19.99');

    $formatSummary->shouldReceive('handle')->once();

    $action->handle($command, $changeSet);
});

it('formats price with yearly recurring', function () {
    $formatSummary = m::mock(FormatSummaryAction::class);
    $action = new FormatAnalysisAction($formatSummary);
    $command = m::mock(Command::class);

    $definition = new PriceDefinition(
        amount: 29999,
        currency: 'usd',
        recurring: ['interval' => 'year', 'interval_count' => 1],
    );

    $priceChange = new PriceChange(
        productKey: 'premium-plan',
        priceType: 'yearly',
        type: ChangeTypeEnum::Created,
        definition: $definition,
        existingPrice: null,
        resultPrice: null,
    );

    $changeSet = new ChangeSet([], [$priceChange]);

    $command->shouldReceive('info')->once();
    $command->shouldReceive('newLine')->times(3);
    $command->shouldReceive('line')->with('<fg=green>Products:</>');
    $command->shouldReceive('line')->with('<fg=green>Prices:</>');

    $command->shouldReceive('line')
        ->once()
        ->with('  + premium-plan.yearly ($299.99/year)');

    $formatSummary->shouldReceive('handle')->once();

    $action->handle($command, $changeSet);
});

it('formats price with multi-interval recurring', function () {
    $formatSummary = m::mock(FormatSummaryAction::class);
    $action = new FormatAnalysisAction($formatSummary);
    $command = m::mock(Command::class);

    $definition = new PriceDefinition(
        amount: 9999,
        currency: 'usd',
        recurring: ['interval' => 'month', 'interval_count' => 3],
    );

    $priceChange = new PriceChange(
        productKey: 'quarterly-plan',
        priceType: 'quarterly',
        type: ChangeTypeEnum::Created,
        definition: $definition,
        existingPrice: null,
        resultPrice: null,
    );

    $changeSet = new ChangeSet([], [$priceChange]);

    $command->shouldReceive('info')->once();
    $command->shouldReceive('newLine')->times(3);
    $command->shouldReceive('line')->with('<fg=green>Products:</>');
    $command->shouldReceive('line')->with('<fg=green>Prices:</>');

    $command->shouldReceive('line')
        ->once()
        ->with('  + quarterly-plan.quarterly ($99.99/every 3 months)');

    $formatSummary->shouldReceive('handle')->once();

    $action->handle($command, $changeSet);
});

it('formats price with euro currency', function () {
    $formatSummary = m::mock(FormatSummaryAction::class);
    $action = new FormatAnalysisAction($formatSummary);
    $command = m::mock(Command::class);

    $definition = new PriceDefinition(
        amount: 1999,
        currency: 'eur',
        recurring: ['interval' => 'month'],
    );

    $priceChange = new PriceChange(
        productKey: 'basic-plan',
        priceType: 'monthly',
        type: ChangeTypeEnum::Created,
        definition: $definition,
        existingPrice: null,
        resultPrice: null,
    );

    $changeSet = new ChangeSet([], [$priceChange]);

    $command->shouldReceive('info')->once();
    $command->shouldReceive('newLine')->times(3);
    $command->shouldReceive('line')->with('<fg=green>Products:</>');
    $command->shouldReceive('line')->with('<fg=green>Prices:</>');

    $command->shouldReceive('line')
        ->once()
        ->with('  + basic-plan.monthly (€19.99/month)');

    $formatSummary->shouldReceive('handle')->once();

    $action->handle($command, $changeSet);
});
