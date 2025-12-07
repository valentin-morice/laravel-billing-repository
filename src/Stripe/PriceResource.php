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
        ?array $recurring = null,
        ?string $nickname = null
    ): string {
        $data = [
            'product' => $productId,
            'unit_amount' => $amount,
            'currency' => $currency,
        ];

        if ($recurring) {
            $data['recurring'] = $recurring;
        }

        if ($nickname) {
            $data['nickname'] = $nickname;
        }

        $price = Price::create($data);

        return $price->id;
    }

    public function archive(string $priceId): object
    {
        return Price::update($priceId, ['active' => false]);
    }
}
