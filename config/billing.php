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
        //     'description' => 'Portuguese Tax Identification Number',
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
        // ],

        // Example: Subscription product with metadata
        // 'premium' => [
        //     'name' => 'Premium Subscription',
        //     'description' => 'Full-featured premium plan',
        //
        //     // Universal metadata (works with any provider)
        //     'metadata' => [
        //         'tier' => 'premium',
        //         'feature_flag' => 'advanced_analytics',
        //     ],
        //
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

        // Example: Product with Stripe-specific features
        // 'enterprise' => [
        //     'name' => 'Enterprise Plan',
        //     'description' => 'Custom enterprise solution',
        //
        //     // Universal metadata
        //     'metadata' => [
        //         'tier' => 'enterprise',
        //         'custom_field' => 'value',
        //     ],
        //
        //     // Stripe-specific features (flat in config, not nested)
        //     'tax_code' => 'txcd_10000000',  // Stripe tax code
        //     'statement_descriptor' => 'MYAPP ENT',  // Appears on customer's credit card statement
        //
        //     'prices' => [
        //         'monthly' => [
        //             'amount' => 9999,
        //             'currency' => 'usd',
        //             'recurring' => ['interval' => 'month'],
        //             'nickname' => 'Enterprise Monthly',
        //
        //             // Universal metadata
        //             'metadata' => [
        //                 'billing_cycle' => 'monthly',
        //             ],
        //
        //             // Universal trial period
        //             'trial_period_days' => 14,  // 14-day free trial
        //
        //             // Stripe-specific features
        //             'tax_behavior' => 'exclusive',  // Tax calculated separately
        //             'lookup_key' => 'enterprise_monthly',  // Unique identifier for easy price lookup
        //         ],
        //     ],
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Generated Enums Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where generated enum files (ProductKey, PriceKey) are placed.
    | By default, enums are generated in your app's Enums/Billing directory.
    |
    | After importing or deploying, you can use these enums for type-safe
    | access to your products and prices:
    |
    |   BillingRepository::product(ProductKey::Premium)
    |   BillingRepository::price(PriceKey::PremiumMonthly)
    |
    */

    'enums' => [
        'path' => app_path('Enums/Billing'),
        'namespace' => 'App\\Enums\\Billing',
    ],

];
