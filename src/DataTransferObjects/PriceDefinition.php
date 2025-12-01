<?php

namespace ValentinMorice\LaravelStripeRepository\DataTransferObjects;

readonly class PriceDefinition
{
    public function __construct(
        public int $amount,
        public string $currency = 'eur',
        public ?array $recurring = null,
    ) {
    }
}
