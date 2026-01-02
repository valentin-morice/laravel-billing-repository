<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\Models;

use ValentinMorice\LaravelBillingRepository\Exceptions\BillingException;

class PriceNotFoundException extends BillingException
{
    public static function forProductAndType(string $productKey, string $priceType): self
    {
        return new self("Price '{$priceType}' not found for product '{$productKey}'");
    }
}
