<?php

use ValentinMorice\LaravelBillingRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\DataTransferObjects\ProductDefinition;

// config for ValentinMorice/LaravelBillingRepository
return [

    /*
    |--------------------------------------------------------------------------
    | Billing Provider
    |--------------------------------------------------------------------------
    |
    | The billing provider to use. Currently supported: stripe
    */

    'provider' => env('BILLING_PROVIDER'),

    /*
    |--------------------------------------------------------------------------
    | Billing Provider API Key
    |--------------------------------------------------------------------------
    |
    | Your billing provider API key. Set this in your .env file.
    | For Stripe, use your secret key. Other providers may use different keys.
    |
    */

    'api_key' => env('BILLING_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Products Configuration
    |--------------------------------------------------------------------------
    |
    | Define your products and their associated prices here.
    | The array key becomes the product identifier (stored in the database).
    |
    | After defining products, run: php artisan billing:deploy
    |
    */

    'products' => [

        // Example: One-time payment products
        // 'nif' => new ProductDefinition(
        //     name: 'NIF Portugal',
        //     prices: [
        //         'default' => new PriceDefinition(amount: 12000),  // €120.00
        //         'zero' => new PriceDefinition(amount: 0),         // Free
        //     ],
        //     description: 'Portuguese Tax Identification Number',
        // ),

        // Example: Subscription product
        // 'premium' => new ProductDefinition(
        //     name: 'Premium Subscription',
        //     prices: [
        //         'monthly' => new PriceDefinition(
        //             amount: 999,
        //             currency: 'eur',
        //             recurring: ['interval' => 'month'],
        //         ),
        //         'yearly' => new PriceDefinition(
        //             amount: 9900,
        //             currency: 'eur',
        //             recurring: ['interval' => 'year'],
        //         ),
        //     ],
        // ),

    ],

];
