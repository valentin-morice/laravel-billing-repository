# Stripe Config Package

## Overview

A Laravel package for managing Stripe products and prices as code (config-as-code approach, similar to Convex/Prisma schemas). This package allows you to define your Stripe product catalog in version-controlled configuration files and deploy them to Stripe via CLI commands.

## The Problem

Traditional Stripe integration has several pain points:

1. **Manual dashboard management** - Products/prices created via Stripe dashboard aren't version controlled
2. **Environment synchronization** - Hard to keep test/staging/production in sync
3. **Type safety** - No IDE autocomplete or type checking for product/price IDs
4. **Code review** - Can't review pricing changes in pull requests
5. **Deployment issues** - No clear deployment strategy for pricing changes

## The Solution

**Define products in config → Deploy to Stripe → Store IDs in DB → Use type-safe constants**

```php
// config/stripe-products.php (version controlled)
'products' => [
    'nif' => new ProductDefinition(  // Array key becomes the product identifier
        name: 'NIF Portugal',
        prices: [
            'default' => new PriceDefinition(12000),
            'zero' => new PriceDefinition(0),
        ],
    ),
]

// Deploy
php artisan stripe:deploy

// Use in code with autocomplete
$priceId = StripeConfig::priceId(StripeProduct::NIF, StripePrice::DEFAULT);
$productId = StripeConfig::productId(StripeProduct::NIF);
```

## Architecture

### 1. Config as Source of Truth

Products are defined using DTOs in `config/stripe-products.php`:

```php
return [
    'products' => [
        'product_key' => new ProductDefinition(  // Array key = DB key (e.g., 'nif', 'social_sec')
            name: 'Product Display Name',        // Name shown in Stripe dashboard
            description: 'Optional description', // Optional
            prices: [
                'price_type' => new PriceDefinition(  // Array key = price type
                    amount: 12000,                    // Amount in cents
                    currency: 'eur',                  // ISO currency code (default: 'eur')
                    recurring: null,                  // null for one-time, or ['interval' => 'month']
                ),
            ],
        ),
    ],
];
```

**Key Design Decision:** The array keys (`'product_key'` and `'price_type'`) become the identifiers stored in the database. This eliminates redundancy and ensures keys can't mismatch.

**Benefits:**
- Version controlled
- Code reviewed in PRs
- CI/CD deployable
- Type-safe with DTOs

### 2. Database Storage

The package creates two tables to store Stripe ID mappings:

#### `stripe_products` table
```
id | key          | stripe_id      | name           | active | created_at | updated_at
---|--------------|----------------|----------------|--------|------------|------------
1  | nif          | prod_abc123    | NIF Portugal   | true   | ...        | ...
2  | social_sec   | prod_def456    | Social Security| true   | ...        | ...
```

#### `stripe_prices` table
```
id | product_id | type     | stripe_id      | amount | currency | recurring | active | created_at | updated_at
---|------------|----------|----------------|--------|----------|-----------|--------|------------|------------
1  | 1          | default  | price_xyz789   | 12000  | eur      | null      | true   | ...        | ...
2  | 1          | zero     | price_abc123   | 0      | eur      | null      | true   | ...        | ...
3  | 2          | monthly  | price_def456   | 5000   | eur      | {"interval":"month"} | true | ... | ...
```

**Why store in database?**
- Stripe IDs are environment-specific (test vs prod have different IDs)
- Can't commit IDs to git
- Need fast lookups without hitting Stripe API
- Track which prices are active vs archived

**How array keys become database keys:**
The deploy command reads your config and uses the array keys as identifiers:
```php
// From config: 'nif' => new ProductDefinition(...)
// Becomes DB row: key = 'nif', stripe_id = 'prod_abc123'

// From config: 'default' => new PriceDefinition(12000)
// Becomes DB row: type = 'default', stripe_id = 'price_xyz789'
```
This ensures the identifiers you use in code (`StripeProduct::NIF`) always match what's in the database.

### 3. Auto-Generated Constants

Constants are generated in the package's models after deployment:

**`vendor/anchorless/stripe-config/src/Models/StripeProduct.php`**
```php
class StripeProduct extends Model
{
    // Auto-generated constants (do not edit manually)
    public const NIF = 'nif';
    public const SOCIAL_SEC = 'social_sec';
    public const NIE = 'nie';
    
    // ... model relationships and methods
}
```

**`vendor/anchorless/stripe-config/src/Models/StripePrice.php`**
```php
class StripePrice extends Model
{
    // Auto-generated constants (do not edit manually)
    public const DEFAULT = 'default';
    public const ZERO = 'zero';
    public const MONTHLY = 'monthly';
    public const DISCOUNTED = 'discounted';
    
    // ... model relationships and methods
}
```

**Why in vendor models, not user's app?**
- Avoids complexity of generating enums in user's codebase
- No need for separate generation command
- Always in sync with package
- Simple, predictable location

### 4. Helper Facade

**`Anchorless\StripeConfig\StripeConfig`** helper provides easy access to Stripe IDs:

```php
// Get Stripe price ID
$priceId = StripeConfig::priceId(StripeProduct::NIF, StripePrice::DEFAULT);
// Returns: 'price_xyz789'

// Get Stripe product ID
$productId = StripeConfig::productId(StripeProduct::NIF);
// Returns: 'prod_abc123'

// Get all active prices for a product
$prices = StripeConfig::prices(StripeProduct::NIF);
// Returns: Collection of StripePrice models

// Get product model
$product = StripeConfig::product(StripeProduct::NIF);
// Returns: StripeProduct model instance

// Direct model usage (if needed)
$product = \Anchorless\StripeConfig\Models\StripeProduct::where('key', 'nif')->first();
$prices = $product->prices()->active()->get();
```

## File Structure

### Package Files (created by package developer)

```
vendor/anchorless/stripe-config/
├── src/
│   ├── Models/
│   │   ├── StripeProduct.php           # Product model with auto-generated constants
│   │   └── StripePrice.php             # Price model with auto-generated constants
│   ├── Commands/
│   │   ├── StripeDeployCommand.php     # php artisan stripe:deploy
│   │   └── StripeImportCommand.php     # php artisan stripe:import
│   ├── DTOs/
│   │   ├── ProductDefinition.php       # Product DTO for config
│   │   └── PriceDefinition.php         # Price DTO for config
│   ├── StripeConfig.php                # Helper facade for accessing IDs
│   ├── StripeConfigServiceProvider.php # Package service provider
│   └── StripeDeployer.php              # Core deployment logic
├── database/
│   └── migrations/
│       ├── create_stripe_products_table.php
│       └── create_stripe_prices_table.php
├── config/
│   └── stripe-config.php               # Package settings
├── composer.json
└── README.md
```

### User's Application Files (created after installation)

```
app-root/
├── config/
│   ├── stripe-config.php          # Published package config (optional customization)
│   └── stripe-products.php        # Product definitions (USER CREATES & COMMITS)
└── database/
    └── migrations/
        ├── xxxx_create_stripe_products_table.php   # Published migration
        └── xxxx_create_stripe_prices_table.php     # Published migration
```

**Important:** Only `config/stripe-products.php` is committed to git. The database contains environment-specific Stripe IDs.

## How Updates Are Managed

### Product Updates

| Scenario | Action | Result |
|----------|--------|--------|
| **Add new product to config** | Creates new product in Stripe | New row in `stripe_products`, `active: true` |
| **Update product name/description** | Updates existing product in Stripe | Updates `stripe_products` row |
| **Remove product from config** | Archives product in Stripe | Sets `active: false` in `stripe_products` |
| **Re-add previously removed product** | Un-archives existing product in Stripe | Sets `active: true` in `stripe_products` |

### Price Updates

| Scenario | Action | Result |
|----------|--------|--------|
| **Add new price type** | Creates new price in Stripe | New row in `stripe_prices`, `active: true` |
| **Change price amount** | Creates new price, archives old | New row created, old row set to `active: false` |
| **Remove price from config** | Archives price in Stripe | Sets `active: false` in `stripe_prices` |
| **Re-add price with same amount** | Creates new price (Stripe limitation) | New row in `stripe_prices` |

**Why can't we un-archive prices?**
- Stripe API doesn't support reactivating archived prices
- Prices are immutable in Stripe (by design for subscription integrity)
- When amount changes, must create new price and archive old one
- Active subscriptions continue using archived prices until they renew

### Deployment Flow

```
1. Developer updates config/stripe-products.php
   └─> Commits and pushes to git

2. Deployment: php artisan stripe:deploy
   ├─> Reads ProductDefinition DTOs from config
   ├─> For each product:
   │   ├─> Check if exists in stripe_products table
   │   ├─> If exists:
   │   │   ├─> Is it archived? → Un-archive in Stripe, set active: true
   │   │   └─> Update name/description if changed
   │   └─> If new: Create in Stripe, insert into stripe_products
   │
   ├─> For each price in product:
   │   ├─> Check if exists with same type and amount
   │   ├─> If amount changed:
   │   │   ├─> Create new price in Stripe
   │   │   ├─> Insert new row in stripe_prices (active: true)
   │   │   └─> Archive old price (active: false)
   │   └─> If new type: Create in Stripe, insert into stripe_prices
   │
   ├─> For products/prices removed from config:
   │   └─> Archive in Stripe, set active: false in DB
   │
   └─> Generate constants in vendor models
       ├─> Read all product keys from stripe_products
       ├─> Read all price types from stripe_prices
       ├─> Write PHP constants to StripeProduct.php
       └─> Write PHP constants to StripePrice.php

3. Application code uses type-safe constants
   └─> StripeConfig::priceId(StripeProduct::NIF, StripePrice::DEFAULT)
```

### Import/Sync Command

```bash
php artisan stripe:import
```

**Purpose:** Pull existing products/prices from Stripe into local database and optionally generate config file.

**Use cases:**
- **Initial migration** - Onboard existing Stripe products to config-as-code
- **Database recovery** - Repopulate local DB after data loss
- **Config generation** - Auto-generate config file from existing Stripe products
- **Auditing** - Compare Stripe dashboard vs local DB/config

**Basic Usage (DB only):**
```bash
# Sync Stripe → DB (no config generation)
php artisan stripe:import --db-only
```
Populates database with existing Stripe products/prices, useful for recovery.

**Generate Config:**
```bash
# Generate config file from Stripe
php artisan stripe:import --generate-config
```
Creates `config/stripe-products.php` with ProductDefinition DTOs from your Stripe products.

**Interactive Mode:**
```bash
php artisan stripe:import --interactive
```
- Prompts for each product: "Import this product? (yes/no/skip)"
- Lets you customize the key name (suggests snake_case from product name)
- Preview the generated config before writing

**Example Output:**
```
Fetching products from Stripe...
Found 3 products:

1. NIF Portugal (prod_abc123) - 2 prices
   Suggested key: nif_portugal
   Custom key [nif_portugal]: nif
   ✓ Imported

2. Social Security (prod_def456) - 1 price
   Suggested key: social_security
   Custom key [social_security]: 
   ✓ Imported

✓ Populated database with 2 products, 3 prices
✓ Generated config/stripe-products.php
✓ Generated constants

Review the config and commit to git when ready.
```

## Usage Examples

### 1. Define Products

```php
// config/stripe-products.php
return [
    'products' => [
        'nif' => new ProductDefinition(
            name: 'NIF Portugal',
            description: 'Portuguese Tax ID application',
            prices: [
                'default' => new PriceDefinition(12000),
                'zero' => new PriceDefinition(0),
            ],
        ),
        
        'saas_subscription' => new ProductDefinition(
            name: 'SaaS Pro Plan',
            prices: [
                'monthly' => new PriceDefinition(
                    amount: 2900,
                    currency: 'eur',
                    recurring: ['interval' => 'month'],
                ),
                'yearly' => new PriceDefinition(
                    amount: 29000,
                    currency: 'eur',
                    recurring: ['interval' => 'year'],
                ),
            ],
        ),
    ],
];
```

### 2. Deploy to Stripe

```bash
php artisan stripe:deploy
```

Output:
```
Deploying Stripe products...
✓ Created product: NIF Portugal (prod_abc123)
  ✓ Created price: default (price_xyz789)
  ✓ Created price: zero (price_abc456)
  
✓ Created product: SaaS Pro Plan (prod_def789)
  ✓ Created price: monthly (price_ghi012)
  ✓ Created price: yearly (price_jkl345)
  
✓ Generated constants in StripeProduct.php
✓ Generated constants in StripePrice.php

Deployment complete!
```

### 3. Use in Code

```php
use Anchorless\StripeConfig\Models\StripeProduct;
use Anchorless\StripeConfig\Models\StripePrice;
use Anchorless\StripeConfig\StripeConfig;

// Get Stripe price ID (for creating checkout session)
$priceId = StripeConfig::priceId(StripeProduct::NIF, StripePrice::DEFAULT);

// Get Stripe product ID (if needed for product-level operations)
$productId = StripeConfig::productId(StripeProduct::NIF);

// Create Stripe checkout session
$checkout = $stripe->checkout->sessions->create([
    'line_items' => [[
        'price' => $priceId,
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => route('success'),
]);

// Get all active prices for a product
$nifPrices = StripeConfig::prices(StripeProduct::NIF);

// Get product model with relationships
$product = StripeConfig::product(StripeProduct::NIF);

// Use in validation rules
$rules = [
    'price_type' => ['required', Rule::in([
        StripePrice::DEFAULT,
        StripePrice::ZERO,
    ])],
];
```

### 4. Update Price (e.g., raise price from €120 to €130)

```php
// config/stripe-products.php
'nif' => new ProductDefinition(
    name: 'NIF Portugal',
    prices: [
        'default' => new PriceDefinition(13000), // Changed from 12000
        'zero' => new PriceDefinition(0),
    ],
),
```

```bash
php artisan stripe:deploy
```

Output:
```
Deploying Stripe products...
✓ Product unchanged: NIF Portugal (prod_abc123)
  ✓ Created new price: default (price_new999) - amount changed
  ✓ Archived old price: default (price_xyz789)
  ✓ Price unchanged: zero (price_abc456)

Deployment complete!
```

**Result:**
- Old price `price_xyz789` remains in Stripe (archived) for existing subscriptions
- New price `price_new999` is used for new checkouts
- Database has both prices, but helper only returns active one

## Benefits

1. **Version Control** - All pricing changes reviewed in PRs
2. **Type Safety** - IDE autocomplete, compile-time checks
3. **Environment Parity** - Same config across test/staging/prod
4. **Audit Trail** - Git history shows all pricing changes
5. **CI/CD Ready** - Deploy pricing as part of deployment pipeline
6. **No Dashboard Drift** - Config is source of truth, not Stripe dashboard
7. **Rollback Support** - Git revert → redeploy
8. **Team Collaboration** - Clear ownership and review process

## Comparison to Manual Approach

| Aspect | Manual (Dashboard) | This Package |
|--------|-------------------|--------------|
| Version control | ❌ No | ✅ Yes (config committed to git) |
| Code review | ❌ No | ✅ Yes (PR reviews) |
| Type safety | ❌ No (magic strings) | ✅ Yes (constants) |
| Environment sync | ❌ Manual copying | ✅ Automatic (same config) |
| Rollback | ❌ Manual | ✅ Git revert + redeploy |
| CI/CD integration | ❌ No | ✅ Yes |
| Audit trail | ⚠️ Stripe logs only | ✅ Git history |
| Team collaboration | ⚠️ Dashboard access needed | ✅ Standard dev workflow |

## Future Features & Improvements

#### Core Features
- **Dry-run mode** - Preview changes before deploying (`--dry-run`)
  ```bash
  php artisan stripe:deploy --dry-run
  # Shows: "Would create product 'nif', would archive price 'default'"
  ```

- **Visual diff** - Show what will change before deploy
  ```bash
  php artisan stripe:diff
  # Output:
  # + Product: NIE (new)
  # ~ Product: NIF (price changed: 12000 → 13000)
  # - Product: Old Service (removed, will archive)
  ```

- **Rollback command** - `php artisan stripe:rollback`
  - Revert to previous deployment state
  - Track deployment history in database
  - Git-based rollback support

- **Validation command** - `php artisan stripe:validate`
  - Check config syntax before deploy
  - Verify no duplicate keys
  - Ensure currency codes are valid
  - Warning for large price changes (>20%)

#### Deployment Features
- **Audit logs** - Track all deployments and changes
  - Who deployed what and when
  - Deployment history table
  - Changelog generation

- **Deployment confirmation** - Require confirmation for production
  ```bash
  php artisan stripe:deploy
  # "You are deploying to PRODUCTION. Type 'yes' to continue:"
  ```

- **Partial deployments** - Deploy only specific products
  ```bash
  php artisan stripe:deploy --only=nif,social_sec
  ```

#### Advanced Features
- **Multi-environment config** - Different pricing per environment
  ```php
  'nif' => new ProductDefinition(
      name: 'NIF Portugal',
      prices: [
          'default' => new PriceDefinition(
              amount: env('APP_ENV') === 'production' ? 12000 : 100,
          ),
      ],
  ),
  ```

- **Metadata support** - Add custom metadata to products/prices
  ```php
  'nif' => new ProductDefinition(
      name: 'NIF Portugal',
      metadata: [
          'category' => 'tax_id',
          'country' => 'PT',
          'processing_time' => '2-4 weeks',
      ],
  ),
  ```

- **Price archival policies** - Auto-archive prices after X days
  ```php
  // config/stripe-config.php
  'auto_archive_after_days' => 90, // Archive inactive prices after 90 days
  ```

- **Webhook integration** - Sync changes from Stripe back to DB
  - Listen for product.created, product.updated events
  - Keep DB in sync if changes made via dashboard
  - Alert on config drift

- **Tax behavior support** - Define tax behavior per price
  ```php
  'default' => new PriceDefinition(
      amount: 12000,
      tax_behavior: 'exclusive', // or 'inclusive', 'unspecified'
  ),
  ```

- **Tiered pricing support** - For complex pricing models
  ```php
  'usage_based' => new PriceDefinition(
      billing_scheme: 'tiered',
      tiers: [
          ['up_to' => 100, 'unit_amount' => 1000],
          ['up_to' => 500, 'unit_amount' => 800],
          ['up_to' => 'inf', 'unit_amount' => 500],
      ],
  ),
  ```

#### Developer Experience
- **IDE autocomplete** - PhpStorm/VSCode plugin for config autocomplete
- **GitHub Action** - Pre-built action for CI/CD deployments
- **Terraform provider** - Manage Stripe via Terraform using this package
- **Health checks** - `php artisan stripe:health`
  - Check if DB is in sync with Stripe
  - Detect orphaned products/prices
  - Report on active subscriptions using archived prices

#### Monitoring & Alerts
- **Deployment notifications** - Slack/Discord notifications on deploy
- **Price change alerts** - Alert when prices change by >X%
- **Dashboard** - Web UI showing deployment history, active products
- **Metrics** - Track deployment frequency, success rate, rollbacks

## Installation (Planned)

```bash
composer require anchorless/stripe-config

php artisan vendor:publish --tag=stripe-config-migrations
php artisan migrate

php artisan vendor:publish --tag=stripe-config-config
```

Create `config/stripe-products.php` with your product definitions, then:

```bash
php artisan stripe:deploy
```

## Configuration

```php
// config/stripe-config.php
return [
    'stripe_key' => env('STRIPE_SECRET_KEY'),
    'stripe_api_version' => '2023-10-16',
    
    // Archive products/prices removed from config?
    'archive_on_removal' => true,
    
    // Generate constants after deployment?
    'generate_constants' => true,
    
    // Path to product definitions
    'products_config' => config_path('stripe-products.php'),
];
```

---

## Summary

This package treats Stripe products as infrastructure-as-code. Define your catalog in config, version control it, review it, deploy it—just like database migrations or application config. The database stores environment-specific IDs, auto-generated constants provide type safety, and the deployment command handles all the Stripe API complexity including archiving and price immutability.
