<?php

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
    | Or import from your provider: php artisan billing:import --generate-config
    |
    */

    'products' => [

        // Example: One-time payment product
        // 'nif' => [
        //     'name' => 'NIF Portugal',
        //     'prices' => [
        //         'default' => [
        //             'amount' => 12000,  // €120.00
        //             'currency' => 'eur',
        //         ],
        //         'zero' => [
        //             'amount' => 0,  // Free
        //             'currency' => 'eur',
        //         ],
        //     ],
        //     'description' => 'Portuguese Tax Identification Number',
        // ],

        // Example: Subscription product
        // 'premium' => [
        //     'name' => 'Premium Subscription',
        //     'prices' => [
        //         'monthly' => [
        //             'amount' => 999,
        //             'currency' => 'eur',
        //             'recurring' => ['interval' => 'month'],
        //         ],
        //         'yearly' => [
        //             'amount' => 9900,
        //             'currency' => 'eur',
        //             'recurring' => ['interval' => 'year'],
        //         ],
        //     ],
        // ],

    ],

];
