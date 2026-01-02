<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Actions;

use Throwable;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;

class FetchProductsFromProviderAction
{
    public function __construct(
        protected ProviderClientInterface $client,
    ) {}

    /**
     * @return iterable<object>
     *
     * @throws Throwable
     */
    public function handle(): iterable
    {
        return $this->client->product()->all();
    }
}
