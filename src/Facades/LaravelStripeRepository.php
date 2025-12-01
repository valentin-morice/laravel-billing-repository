<?php

namespace ValentinMorice\LaravelStripeRepository\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ValentinMorice\LaravelStripeRepository\LaravelStripeRepository
 */
class LaravelStripeRepository extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ValentinMorice\LaravelStripeRepository\LaravelStripeRepository::class;
    }
}
