<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Actions;

use Throwable;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;

class FetchPricesForProductAction
{
    public function __construct(
        protected ProviderClientInterface $client,
    ) {}

    /**
     * @return iterable<object>
     *
     * @throws Throwable
     */
    public function handle(string $productId): iterable
    {
        return $this->client->price()->allForProduct($productId);
    }
}
