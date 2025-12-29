<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Service;

use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

readonly class PriceArchiveResult
{
    /**
     * @param  array<int, BillingPrice>  $archivedPrices
     */
    public function __construct(
        public array $archivedPrices,
        public int $count,
    ) {}

    /**
     * Create from array of archived prices
     *
     * @param  array<int, BillingPrice>  $archivedPrices
     */
    public static function fromArray(array $archivedPrices): self
    {
        return new self($archivedPrices, count($archivedPrices));
    }
}
