<?php

namespace ValentinMorice\LaravelBillingRepository\Data\Enum;

/**
 * Strategy for handling immutable field changes during deployment
 */
enum ImmutableFieldStrategy: string
{
    /**
     * Archive the old price, create a new one with the same key (current behavior)
     */
    case Archive = 'archive';

    /**
     * Keep the old price active, create a new price with a custom key
     */
    case Duplicate = 'duplicate';

    /**
     * Abort the deployment
     */
    case Cancel = 'cancel';

    /**
     * Get a human-readable description of the strategy
     */
    public function description(): string
    {
        return match ($this) {
            self::Archive => 'Archive old price, create new with same key',
            self::Duplicate => 'Keep old price active, create new price',
            self::Cancel => 'Abort deployment',
        };
    }
}
