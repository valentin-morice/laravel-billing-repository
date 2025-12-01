<?php

namespace ValentinMorice\LaravelStripeRepository\Contracts;

interface StripeClientInterface
{
    public function product(): ProductResourceInterface;

    public function price(): PriceResourceInterface;
}
