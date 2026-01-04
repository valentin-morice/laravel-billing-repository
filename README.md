# Config-as-code for billing providers in Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/valentin-morice/laravel-billing-repository.svg?style=flat-square)](https://packagist.org/packages/valentin-morice/laravel-billing-repository)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/valentin-morice/laravel-billing-repository/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/valentin-morice/laravel-billing-repository/actions?query=workflow%3Aci+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/valentin-morice/laravel-billing-repository/ci.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/valentin-morice/laravel-billing-repository/actions?query=workflow%3Aci+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/valentin-morice/laravel-billing-repository.svg?style=flat-square)](https://packagist.org/packages/valentin-morice/laravel-billing-repository)

A Laravel package that provides config-as-code for billing providers. Define your products, prices, and billing settings in versioned configuration files, then plan and apply changes with artisan commands (similar to Terraform's workflow).

**Currently supported providers:**
- Stripe (more providers coming soon)

## Features

- **Provider-agnostic architecture**: Easy to add support for new billing providers
- **Config-as-code**: Define products and prices in PHP configuration files
- **Version control**: Track billing changes alongside your application code
- **Deploy workflow**: Plan and apply billing changes with simple artisan commands
- **Type-safe**: Leverages PHP DTOs for configuration validation

## Installation

You can install the package via composer:

```bash
composer require valentin-morice/laravel-billing-repository
```

Publish the config file and migrations:

```bash
php artisan vendor:publish --provider="ValentinMorice\LaravelBillingRepository\LaravelBillingRepositoryServiceProvider"
php artisan migrate
```

## Configuration

After publishing the config file, you'll find it at `config/billing.php`. Here's an example configuration:

```php
use ValentinMorice\LaravelBillingRepository\Data\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\ProductDefinition;

return [
    'provider' => env('BILLING_PROVIDER', 'stripe'),
    'api_key' => env('BILLING_API_KEY'),

    'products' => [
        'premium' => new ProductDefinition(
            name: 'Premium Subscription',
            prices: [
                'monthly' => new PriceDefinition(
                    amount: 999,
                    currency: 'eur',
                    recurring: ['interval' => 'month'],
                ),
                'yearly' => new PriceDefinition(
                    amount: 9900,
                    currency: 'eur',
                    recurring: ['interval' => 'year'],
                ),
            ],
        ),
    ],
];
```

## Usage

After configuring your products and prices, deploy them to your billing provider:

```bash
php artisan billing:deploy
```

This command will:
1. Compare your configuration with the current state in your billing provider
2. Show you a plan of changes to be made
3. Apply the changes to your billing provider
4. Sync the changes to your local database

## Architecture

The package uses a provider adapter pattern to support multiple billing providers:

- `ProviderAdapter`: Interface that all billing providers must implement
- `ProviderClientInterface`: Interface for provider-specific API clients
- `StripeAdapter`: Stripe implementation (ships with the package)

Adding a new provider is as simple as implementing the `ProviderAdapter` interface.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Valentin Morice](https://github.com/valentin-morice)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
