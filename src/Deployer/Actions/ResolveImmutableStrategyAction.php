<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Actions;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy;
use ValentinMorice\LaravelBillingRepository\Exceptions\Deployer\DeploymentCancelledException;

class ResolveImmutableStrategyAction
{
    /**
     * Resolve the strategy for handling immutable field changes
     *
     * @param  array<string>  $existingKeys  List of existing price keys to avoid collisions
     *
     * @throws DeploymentCancelledException
     */
    public function handle(
        Command $command,
        PriceChange $change,
        array $existingKeys
    ): PriceChange {
        if (! $change->hasImmutableChanges) {
            return $change;
        }

        $this->displayImmutableChanges($command, $change);

        while (true) {
            $strategy = $this->promptForStrategy($command);

            if ($strategy === ImmutableFieldStrategy::Cancel) {
                throw new DeploymentCancelledException('Deployment cancelled by user');
            }

            if ($strategy === ImmutableFieldStrategy::Archive) {
                return $change->withStrategy($strategy);
            }

            // Duplicate strategy - need to get and validate the new key
            $newKey = $this->promptForNewKey($command, $change->priceKey, $existingKeys);

            if ($newKey !== null) {
                return $change->withStrategy($strategy, $newKey);
            }

            // Validation failed, loop will continue
        }
    }

    /**
     * Display immutable changes to the user
     */
    private function displayImmutableChanges(Command $command, PriceChange $change): void
    {
        $command->newLine();
        $command->warn("⚠ Immutable field changes detected for {$change->productKey}.{$change->priceKey}");
        $command->newLine();

        $immutableChanges = $change->getImmutableChanges();
        foreach ($immutableChanges as $field => $values) {
            $oldValue = $this->formatValue($values['old']);
            $newValue = $this->formatValue($values['new']);
            $command->line("  <fg=gray>- {$field}: {$oldValue} → {$newValue}</>");
        }

        $command->newLine();
    }

    /**
     * Prompt user to select a strategy
     */
    private function promptForStrategy(Command $command): ImmutableFieldStrategy
    {
        $choice = $command->choice(
            'How would you like to handle this?',
            [
                ImmutableFieldStrategy::Archive->value => ImmutableFieldStrategy::Archive->description(),
                ImmutableFieldStrategy::Duplicate->value => ImmutableFieldStrategy::Duplicate->description(),
                ImmutableFieldStrategy::Cancel->value => ImmutableFieldStrategy::Cancel->description(),
            ],
            ImmutableFieldStrategy::Archive->value
        );

        return ImmutableFieldStrategy::from($choice);
    }

    /**
     * Prompt for and validate a new price key
     *
     * @param  array<string>  $existingKeys
     * @return string|null The validated key, or null if validation failed
     */
    private function promptForNewKey(Command $command, string $baseKey, array $existingKeys): ?string
    {
        $defaultKey = $this->generateDefaultKey($baseKey, $existingKeys);
        $newKey = $command->ask('Enter key for new price', $defaultKey);

        if (empty($newKey)) {
            $command->error('Key cannot be empty. Please try again.');

            return null;
        }

        if (in_array($newKey, $existingKeys, true)) {
            $command->error("Key '{$newKey}' already exists. Please try again.");

            return null;
        }

        return $newKey;
    }

    /**
     * Generate a default key for the duplicate strategy
     *
     * @param  array<string>  $existingKeys
     */
    private function generateDefaultKey(string $baseKey, array $existingKeys): string
    {
        $suffix = 1;
        while (in_array("{$baseKey}_{$suffix}", $existingKeys, true)) {
            $suffix++;
        }

        return "{$baseKey}_{$suffix}";
    }

    /**
     * Format a value for display
     */
    private function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }
}
