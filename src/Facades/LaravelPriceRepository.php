<?php

namespace ValentinMorice\LaravelPriceRepository\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ValentinMorice\LaravelPriceRepository\LaravelPriceRepository
 */
class LaravelPriceRepository extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ValentinMorice\LaravelPriceRepository\LaravelPriceRepository::class;
    }
}
