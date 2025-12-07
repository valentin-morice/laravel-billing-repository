<?php

namespace ValentinMorice\LaravelStripeRepository\Actions\Deployer\Price;

use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\Models\StripePrice;

class ArchiveAction
{
    public function __construct(
        protected StripeClientInterface $client
    ) {}

    public function handle(StripePrice $price): StripePrice
    {
        $this->client->price()->archive($price->stripe_id);
        $price->update(['active' => false]);

        return $price->fresh();
    }
}
