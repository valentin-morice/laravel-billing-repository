<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Importer;

use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

readonly class ImportedPrice
{
    public function __construct(
        public BillingPrice $price,
        public bool $wasCreated,
    ) {}
}
