<?php

namespace ValentinMorice\LaravelBillingRepository\Facades;

use ValentinMorice\LaravelBillingRepository\Facades\Support\BillingConfigRepository;
use ValentinMorice\LaravelBillingRepository\Facades\Support\BillingResourceRepository;

class BillingRepository
{
    /**
     * Access config-based operations (ProductDefinition from config)
     */
    public static function config(): BillingConfigRepository
    {
        return new BillingConfigRepository;
    }

    /**
     * Access resource-based operations (BillingProduct/BillingPrice from database)
     */
    public static function resource(): BillingResourceRepository
    {
        return new BillingResourceRepository;
    }
}
