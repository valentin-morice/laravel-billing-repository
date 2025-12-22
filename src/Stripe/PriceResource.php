<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe;

use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use ValentinMorice\LaravelBillingRepository\Contracts\PriceResourceInterface;

class PriceResource implements PriceResourceInterface
{
    /**
     * @throws ApiErrorException
     */
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

    /**
     * @throws ApiErrorException
     */
    public function archive(string $priceId): object
    {
        return Price::update($priceId, ['active' => false]);
    }
}
