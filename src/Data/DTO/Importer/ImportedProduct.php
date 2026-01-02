<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Importer;

use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

readonly class ImportedProduct
{
    public function __construct(
        public BillingProduct $product,
        public bool $wasCreated,
    ) {}
}
