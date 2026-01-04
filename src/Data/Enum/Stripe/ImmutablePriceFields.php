<?php

namespace ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe;

use ValentinMorice\LaravelBillingRepository\Data\Enum\Concerns\FiltersImmutableFields;

/**
 * Defines which price fields are immutable in Stripe and require archive+create
 * vs which can be updated in-place
 */
enum ImmutablePriceFields: string
{
    use FiltersImmutableFields;

    case AMOUNT = 'amount';
    case CURRENCY = 'currency';
    case RECURRING = 'recurring';
    case TRIAL_PERIOD_DAYS = 'trialPeriodDays';
}
