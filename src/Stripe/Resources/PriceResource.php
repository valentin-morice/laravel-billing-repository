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
        ?string $nickname = null,
        ?array $metadata = null,
        ?int $trialPeriodDays = null,
        ?string $taxBehavior = null,
        ?string $lookupKey = null
    ): string {
        return $this->retryable(function () use (
            $productId,
            $amount,
            $currency,
            $recurring,
            $nickname,
            $metadata,
            $trialPeriodDays,
            $taxBehavior,
            $lookupKey
        ) {
            $data = [
                'product' => $productId,
                'unit_amount' => $amount,
                'currency' => $currency,
            ];

            if ($recurring) {
                $data['recurring'] = $recurring;

                // trial_period_days goes inside recurring for Stripe
                if ($trialPeriodDays !== null) {
                    $data['recurring']['trial_period_days'] = $trialPeriodDays;
                }
            }

            if ($nickname !== null) {
                $data['nickname'] = $nickname;
            }
            if ($metadata !== null) {
                $data['metadata'] = $metadata;
            }
            if ($taxBehavior !== null) {
                $data['tax_behavior'] = $taxBehavior;
            }
            if ($lookupKey !== null) {
                $data['lookup_key'] = $lookupKey;
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
     * @param  array<string, mixed>  $params
     *
     * @throws ProviderException|Throwable
     */
    public function update(string $priceId, array $params): object
    {
        return $this->retryable(fn () => Price::update($priceId, $params));
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
