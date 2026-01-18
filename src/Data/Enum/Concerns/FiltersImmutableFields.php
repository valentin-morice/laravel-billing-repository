<?php

namespace ValentinMorice\LaravelBillingRepository\Data\Enum\Concerns;

trait FiltersImmutableFields
{
    /**
     * Get all immutable field names
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return array_map(fn (self $field) => $field->value, self::cases());
    }

    /**
     * Check if a field is immutable
     *
     * Supports nested fields using dot notation (e.g., 'stripe.tax_behavior')
     */
    public static function isImmutable(string $field): bool
    {
        $immutableFields = self::all();

        // Direct match
        if (in_array($field, $immutableFields, true)) {
            return true;
        }

        // Check for nested field matches (e.g., 'stripe' with 'stripe.tax_behavior')
        foreach ($immutableFields as $immutableField) {
            // If the immutable field is a nested path and the field matches the parent
            if (str_contains($immutableField, '.')) {
                [$parent, $nested] = explode('.', $immutableField, 2);
                if ($field === $parent) {
                    // The parent field contains an immutable nested field
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a field is mutable (can be updated in-place)
     */
    public static function isMutable(string $field): bool
    {
        return ! self::isImmutable($field);
    }

    /**
     * Filter changes array to only immutable fields
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function filterImmutable(array $changes): array
    {
        $result = [];

        foreach ($changes as $field => $change) {
            // Direct immutable field check
            if (self::isImmutableFieldChange($field, $change)) {
                $result[$field] = $change;
            }
        }

        return $result;
    }

    /**
     * Filter changes array to only mutable fields
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function filterMutable(array $changes): array
    {
        $result = [];

        foreach ($changes as $field => $change) {
            if (! self::isImmutableFieldChange($field, $change)) {
                $result[$field] = $change;
            }
        }

        return $result;
    }

    /**
     * Check if a specific field change involves immutable fields
     *
     * @param  array{old: mixed, new: mixed}  $change
     */
    private static function isImmutableFieldChange(string $field, array $change): bool
    {
        $immutableFields = self::all();

        // Direct match
        if (in_array($field, $immutableFields, true)) {
            return true;
        }

        // Check nested immutable fields within this field
        foreach ($immutableFields as $immutableField) {
            if (str_contains($immutableField, '.')) {
                [$parent, $nested] = explode('.', $immutableField, 2);

                if ($field === $parent) {
                    // Check if the nested field changed
                    $oldValue = is_array($change['old']) ? ($change['old'][$nested] ?? null) : null;
                    $newValue = is_array($change['new']) ? ($change['new'][$nested] ?? null) : null;

                    if ($oldValue !== $newValue) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
