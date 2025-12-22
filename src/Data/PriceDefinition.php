<?php

namespace ValentinMorice\LaravelBillingRepository\Data;

readonly class PriceDefinition
{
    public function __construct(
        public int $amount,
        public string $currency = 'eur',
        public ?array $recurring = null,
        public ?string $nickname = null,
    ) {}
}
