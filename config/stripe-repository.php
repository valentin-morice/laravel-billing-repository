<?php

use ValentinMorice\LaravelStripeRepository\DataTransferObjects\PriceDefinition;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\ProductDefinition;

// config for ValentinMorice/LaravelStripeRepository
return [

    /*
    |--------------------------------------------------------------------------
    | Stripe API Key
    |--------------------------------------------------------------------------
    |
    | Your Stripe secret API key. Set this in your .env file.
    |
    */

    'api_key' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Products Configuration
    |--------------------------------------------------------------------------
    |
    | Define your Stripe products and their associated prices here.
    | The array key becomes the product identifier (stored in the database).
    |
    | After defining products, run: php artisan stripe:deploy
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
