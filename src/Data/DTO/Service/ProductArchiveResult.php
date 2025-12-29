<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Service;

use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

readonly class ProductArchiveResult
{
    /**
     * @param  array<int, BillingProduct>  $archivedProducts
     */
    public function __construct(
        public array $archivedProducts,
        public int $count,
    ) {}

    /**
     * Create from array of archived products
     *
     * @param  array<int, BillingProduct>  $archivedProducts
     */
    public static function fromArray(array $archivedProducts): self
    {
        return new self($archivedProducts, count($archivedProducts));
    }
}
