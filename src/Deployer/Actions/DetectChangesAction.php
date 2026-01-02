<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Actions;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\RecurringConfig;

class DetectChangesAction
{
    /**
     * Detect changes between existing and new values for specified fields
     *
     * @param  array<string>  $fields
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function handle(object $existing, object $definition, array $fields): array
    {
        return collect($fields)
            ->filter(fn ($field) => ! $this->valuesAreEqual($existing->{$field}, $definition->{$field}))
            ->mapWithKeys(fn ($field) => [
                $field => ['old' => $existing->{$field}, 'new' => $definition->{$field}],
            ])
            ->all();
    }

    /**
     * Compare two values, handling RecurringConfig objects
     */
    private function valuesAreEqual(mixed $oldValue, mixed $newValue): bool
    {
        if ($newValue instanceof RecurringConfig) {
            $newValue = $newValue->toArray();
        }

        return $oldValue === $newValue;
    }
}
