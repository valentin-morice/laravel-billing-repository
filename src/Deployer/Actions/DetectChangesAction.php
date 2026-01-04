<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Actions;

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
     * Compare two values, handling DTO objects with toArray() method
     */
    private function valuesAreEqual(mixed $oldValue, mixed $newValue): bool
    {
        if (is_object($newValue) && method_exists($newValue, 'toArray')) {
            $newValue = $newValue->toArray();
        }

        if (is_object($oldValue)) {
            $oldValue = (array) $oldValue;
        }
        if (is_object($newValue)) {
            $newValue = (array) $newValue;
        }

        return $oldValue === $newValue;
    }
}
