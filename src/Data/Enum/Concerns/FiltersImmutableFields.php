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
     */
    public static function isImmutable(string $field): bool
    {
        return in_array($field, self::all(), true);
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
        return array_filter(
            $changes,
            fn (string $field) => self::isImmutable($field),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Filter changes array to only mutable fields
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function filterMutable(array $changes): array
    {
        return array_filter(
            $changes,
            fn (string $field) => self::isMutable($field),
            ARRAY_FILTER_USE_KEY
        );
    }
}
