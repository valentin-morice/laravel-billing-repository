<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\Models;

use ValentinMorice\LaravelBillingRepository\Exceptions\BillingException;

class PriceNotFoundException extends BillingException
{
    public static function forProductAndKey(string $productKey, string $priceKey): self
    {
        return new self("Price '{$priceKey}' not found for product '{$productKey}'");
    }
}
