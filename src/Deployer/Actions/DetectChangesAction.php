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
            ->filter(fn ($field) => $existing->{$field} !== $definition->{$field})
            ->mapWithKeys(fn ($field) => [
                $field => ['old' => $existing->{$field}, 'new' => $definition->{$field}],
            ])
            ->all();
    }
}
