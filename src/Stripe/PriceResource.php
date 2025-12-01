<?php

namespace ValentinMorice\LaravelStripeRepository\Stripe;

use Stripe\Price;
use ValentinMorice\LaravelStripeRepository\Contracts\PriceResourceInterface;

class PriceResource implements PriceResourceInterface
{
    public function create(
        string $productId,
        int $amount,
        string $currency,
        ?array $recurring = null
    ): string {
        $data = [
            'product' => $productId,
            'unit_amount' => $amount,
            'currency' => $currency,
        ];

        if ($recurring) {
            $data['recurring'] = $recurring;
        }

        $price = Price::create($data);

        return $price->id;
    }
}
