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

    /**
     * @throws ProviderException|Throwable
     */
    public function allForProduct(string $productId): iterable
    {
        return $this->retryable(function () use ($productId) {
            $hasMore = true;
            $startingAfter = null;

            while ($hasMore) {
                $params = [
                    'product' => $productId,
                    'limit' => 100,
                ];
                if ($startingAfter !== null) {
                    $params['starting_after'] = $startingAfter;
                }

                $prices = Price::all($params);

                foreach ($prices->data as $price) {
                    yield $price;
                }

                $hasMore = $prices->has_more;
                if ($hasMore && count($prices->data) > 0) {
                    $startingAfter = end($prices->data)->id;
                }
            }
        });
    }
}
