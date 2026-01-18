<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts;

/**
 * Contract for provider-specific immutable field definitions
 *
 * Each billing provider (Stripe, Paddle, etc.) has different fields that
 * cannot be updated in-place and require archive+create operations.
 */
interface ImmutableFieldsInterface
{
    /**
     * Get all immutable field names
     *
     * @return array<string>
     */
    public static function all(): array;

    /**
     * Check if a field is immutable
     */
    public static function isImmutable(string $field): bool;

    /**
     * Check if a field is mutable (can be updated in-place)
     */
    public static function isMutable(string $field): bool;

    /**
     * Filter changes array to only immutable fields
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function filterImmutable(array $changes): array;

    /**
     * Filter changes array to only mutable fields
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function filterMutable(array $changes): array;
}
