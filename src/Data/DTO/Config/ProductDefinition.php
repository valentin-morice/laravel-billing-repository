<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Config;

readonly class ProductDefinition
{
    /**
     * @param  array<string, PriceDefinition>  $prices
     */
    public function __construct(
        public string $name,
        public array $prices,
        public ?string $description = null,
    ) {}
}
