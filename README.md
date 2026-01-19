# Laravel Billing Repository

[![Latest Version on Packagist](https://img.shields.io/packagist/v/valentin-morice/laravel-billing-repository.svg?style=flat-square)](https://packagist.org/packages/valentin-morice/laravel-billing-repository)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/valentin-morice/laravel-billing-repository/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/valentin-morice/laravel-billing-repository/actions?query=workflow%3Aci+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/valentin-morice/laravel-billing-repository.svg?style=flat-square)](https://packagist.org/packages/valentin-morice/laravel-billing-repository)

**Version-controlled billing configuration with type-safe access to provider IDs.**

Define products and prices in PHP config files, deploy them to your billing provider (Stripe), and access provider IDs safely throughout your codebase—no more hardcoded strings or magic values.

## Why This Package?

**The Problem:** Managing billing products/prices requires:
- Hardcoding provider IDs (`price_1ABC...`) scattered across your codebase
- Manual syncing between provider dashboard and application
- No version control or audit trail for billing changes
- Slow API calls every time you need a price ID

**The Solution:** This package provides:
- **Config-as-code**: Define products/prices in versioned PHP files
- **DB caching**: Fast local lookups without API calls
- **Type-safe facade**: Access provider IDs using constant keys
- **Auto-generated constants**: IDE autocomplete and refactor-safe code
- **Deploy workflow**: Plan and apply billing changes like infrastructure-as-code

## Quick Example

**1. Define your products in `config/billing.php`:**

```php
'products' => [
    'premium' => [
        'name' => 'Premium Plan',
        'prices' => [
            'monthly' => [
                'amount' => 999,
                'currency' => 'eur',
                'recurring' => ['interval' => 'month'],
            ],
            'yearly' => [
                'amount' => 9900,
                'currency' => 'eur',
                'recurring' => ['interval' => 'year'],
            ],
        ],
    ],
],
```

**2. Deploy to Stripe and cache locally:**

```bash
php artisan billing:deploy
```

This automatically:
- Creates/updates products and prices in Stripe
- Caches them in your local database
- **Generates type-safe enums for your products and prices**

**3. Use type-safe accessors in your code:**

```php
use App\Enums\Billing\{ProductKey, PriceKey};
use ValentinMorice\LaravelBillingRepository\Facades\BillingRepository;

// Option 1: Direct facade access with string keys
$priceId = BillingRepository::priceId('premium', 'monthly');

// Option 2: Type-safe enums (auto-generated after deploy)
$priceId = BillingRepository::priceId(
    ProductKey::Premium,    // 'premium'
    PriceKey::Monthly       // 'monthly'
);

// Use in your application
$user->checkout($priceId, ['quantity' => 1]);
```

**Before vs After:**

```php
// ❌ Before: Hardcoded magic strings
$checkout = $user->checkout('price_1ABCxyz123', ['quantity' => 1]);

// ✅ After: Type-safe, refactor-friendly
$checkout = $user->checkout(
    BillingRepository::priceId(ProductKey::Premium, PriceKey::Monthly),
    ['quantity' => 1]
);
```

## How It Works

### 1. Database Cache Layer

The package stores products and prices locally in `billing_products` and `billing_prices` tables:
- **Fast lookups**: No API calls needed to get provider IDs
- **Reference data**: Use as relationships in your models (subscriptions, invoices, etc.)
- **Audit trail**: Track what's deployed and when

### 2. Config-as-Code Workflow

Define your billing structure in `config/billing.php` using simple arrays:

```php
return [
    'provider' => env('BILLING_PROVIDER', 'stripe'),
    'api_key' => env('BILLING_API_KEY'),

    'products' => [
        'basic' => [
            'name' => 'Basic Plan',
            'description' => 'Perfect for individuals',
            'prices' => [
                'monthly' => [
                    'amount' => 499,
                    'currency' => 'eur',
                    'recurring' => ['interval' => 'month'],
                ],
            ],
        ],
        'premium' => [
            'name' => 'Premium Plan',
            'description' => 'For power users',
            'prices' => [
                'monthly' => [
                    'amount' => 999,
                    'currency' => 'eur',
                    'recurring' => ['interval' => 'month'],
                ],
                'yearly' => [
                    'amount' => 9900,
                    'currency' => 'eur',
                    'recurring' => ['interval' => 'year'],
                ],
            ],
        ],
    ],
];
```

### 3. Two-Way Sync Commands

#### `billing:import` - Pull from provider
Import existing products/prices from your billing provider into the database:

```bash
# Import to database only (default)
php artisan billing:import --db-only

# Import and generate config file from provider
php artisan billing:import --generate-config
```

Use `--generate-config` when starting with existing Stripe products, or to sync your config with provider changes.

#### `billing:deploy` - Push to provider
Deploy config changes to your billing provider and sync to database:

```bash
# Preview changes (dry-run)
php artisan billing:deploy --dry-run

# Deploy changes
php artisan billing:deploy

# CI/Non-interactive: Auto-archive for immutable field changes
php artisan billing:deploy --archive-all

# CI/Non-interactive: Auto-duplicate for immutable field changes
php artisan billing:deploy --duplicate-all
```

The command shows a plan of changes (create/update/archive) before applying them.

**Handling Immutable Fields:**

Stripe prices have immutable fields (amount, currency, recurring interval) that cannot be updated once created. When you modify these fields in your config, the deploy command detects this and prompts you to choose a strategy:

- **Archive**: Archives the old price in Stripe and creates a new price with the same key. Existing subscriptions using the old price will continue to work, but new subscriptions will use the new price.
- **Duplicate**: Keeps the old price active and creates a new price with an incremented key (e.g., `monthly` → `monthly_1`). Useful when you need both prices to remain available.

In CI environments, use `--archive-all` or `--duplicate-all` to skip interactive prompts.

### 4. Auto-Generated Type-Safe Enums

After running `billing:deploy` or `billing:import`, the package automatically generates backed enum files in your application:

**app/Enums/Billing/ProductKey.php** (auto-generated):
```php
namespace App\Enums\Billing;

enum ProductKey: string
{
    case Basic = 'basic';
    case Premium = 'premium';
}
```

**app/Enums/Billing/PriceKey.php** (auto-generated):
```php
namespace App\Enums\Billing;

enum PriceKey: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
```

**Configurable Location:**

The enum path and namespace are configurable in `config/billing.php`:

```php
'enums' => [
    'path' => app_path('Enums/Billing'),
    'namespace' => 'App\\Enums\\Billing',
],
```

**Benefits:**
- **IDE Autocomplete**: Your editor suggests available products/prices
- **Refactor-Safe**: Rename keys without breaking your codebase
- **Type Safety**: Catch typos at development time, not runtime
- **Self-Documenting**: See all available products/prices in one place

**Enum Case Naming:**
- Product keys: Converted to PascalCase (e.g., `'premium'` → `Premium`)
- Price types: Converted to PascalCase (e.g., `'monthly'` → `Monthly`)
- Handles special cases: Separators trigger new words (e.g., `'pro-plan'` → `ProPlan`)
- Avoids collisions: Reserved PHP keywords get `Case` suffix

### 5. Type-Safe Facade

Access provider IDs throughout your codebase:

```php
use App\Enums\Billing\{ProductKey, PriceKey};
use ValentinMorice\LaravelBillingRepository\Facades\BillingRepository;

// Get a specific price ID (with enums)
$priceId = BillingRepository::priceId(ProductKey::Premium, PriceKey::Monthly);
// Returns: "price_1ABC..." (Stripe price ID)

// Or use string keys directly
$priceId = BillingRepository::priceId('premium', 'monthly');

// Get a product ID
$productId = BillingRepository::productId(ProductKey::Premium);
// Returns: "prod_XYZ..." (Stripe product ID)

// Use in your application
$user->checkout($priceId, ['quantity' => 1]);
$user->subscription('default')->swap($priceId);
```

**Error Handling:**
```php
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\{
    ProductNotFoundException,
    PriceNotFoundException
};

try {
    $priceId = BillingRepository::priceId('nonexistent', 'monthly');
} catch (ProductNotFoundException $e) {
    // Product not found or inactive
} catch (PriceNotFoundException $e) {
    // Price not found or inactive for this product
}
```

**Additional Facade Methods:**

```php
// Access config-level operations
$productDef = BillingRepository::config()->product('premium');
$priceDef = BillingRepository::config()->price('premium', 'monthly');

// Access database resources
$product = BillingRepository::resource()->product('premium'); // BillingProduct model
$prices = BillingRepository::resource()->prices('premium');   // Collection of BillingPrice
```

## Installation

```bash
composer require valentin-morice/laravel-billing-repository
```

Publish config and run migrations:

```bash
php artisan vendor:publish --provider="ValentinMorice\LaravelBillingRepository\LaravelBillingRepositoryServiceProvider"
php artisan migrate
```

Set your billing provider credentials in `.env`:

```env
BILLING_PROVIDER=stripe
BILLING_API_KEY=sk_test_...
```

## Typical Workflows

### Starting Fresh
1. Define products in `config/billing.php`
2. Run `php artisan billing:deploy` to create them in Stripe
3. Enums are auto-generated in `app/Enums/Billing/`
4. Use `BillingRepository::priceId(ProductKey::Premium, PriceKey::Monthly)` in your code

### Migrating Existing Stripe Setup
1. Run `php artisan billing:import --generate-config` to pull existing products
2. Review and version-control the generated config
3. Enums are auto-generated from imported data
4. Use enums to replace hardcoded IDs throughout your codebase

### Making Changes
1. Update `config/billing.php`
2. Run `php artisan billing:deploy --dry-run` to preview
3. Run `php artisan billing:deploy` to apply
4. Enums are automatically regenerated

### CI/CD Deployment
For automated deployments, use flags to skip interactive prompts:
```bash
# Auto-archive old prices when immutable fields change
php artisan billing:deploy --archive-all

# Auto-duplicate prices when immutable fields change
php artisan billing:deploy --duplicate-all
```

## Real-World Example

```php
// config/billing.php
'products' => [
    'starter' => [
        'name' => 'Starter Plan',
        'prices' => [
            'monthly' => [
                'amount' => 999,
                'currency' => 'eur',
                'recurring' => ['interval' => 'month'],
            ],
        ],
    ],
    'pro' => [
        'name' => 'Pro Plan',
        'prices' => [
            'monthly' => [
                'amount' => 2999,
                'currency' => 'eur',
                'recurring' => ['interval' => 'month'],
            ],
            'yearly' => [
                'amount' => 29900,
                'currency' => 'eur',
                'recurring' => ['interval' => 'year'],
            ],
        ],
    ],
],
```

After `php artisan billing:deploy`, use everywhere:

```php
// Checkout controller
public function checkout(Request $request)
{
    $plan = $request->input('plan');     // 'starter' or 'pro'
    $interval = $request->input('interval'); // 'monthly' or 'yearly'

    $priceId = BillingRepository::priceId($plan, $interval);

    return $request->user()->checkout($priceId, [
        'success_url' => route('checkout.success'),
        'cancel_url' => route('checkout.cancel'),
    ]);
}

// Subscription management
public function upgrade(User $user)
{
    $user->subscription('default')->swap(
        BillingRepository::priceId(ProductKey::Pro, PriceKey::Yearly)
    );
}

// Pricing page
public function pricing()
{
    return view('pricing', [
        'starterMonthly' => BillingRepository::priceId(ProductKey::Starter, PriceKey::Monthly),
        'proMonthly' => BillingRepository::priceId(ProductKey::Pro, PriceKey::Monthly),
        'proYearly' => BillingRepository::priceId(ProductKey::Pro, PriceKey::Yearly),
    ]);
}
```

## Provider Support

Currently supported:
- **Stripe** (ships with package)

The package uses a provider adapter pattern—adding new providers (Paddle, PayPal) requires implementing the `ProviderAdapter` interface.

## Requirements

- PHP ^8.3
- Laravel ^11.31 or ^12.0

## Testing

```bash
composer test
composer test-coverage
composer format
composer analyse
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
