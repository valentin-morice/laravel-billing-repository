<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Resources;

use Stripe\Price;
use Throwable;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderException;
use ValentinMorice\LaravelBillingRepository\Stripe\Concerns\RetriesStripeRequests;

class PriceResource implements PriceResourceInterface
{
    use RetriesStripeRequests;

    /**
     * @throws ProviderException|Throwable
     */
    public function create(
        string $productId,
        int $amount,
        string $currency,
        ?array $recurring = null,
        ?string $nickname = null
    ): string {
        return $this->retryable(function () use ($productId, $amount, $currency, $recurring, $nickname) {
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
        });
    }

    /**
     * @throws ProviderException|Throwable
     */
    public function archive(string $priceId): object
    {
        return $this->retryable(fn () => Price::update($priceId, ['active' => false]));
    }
}
