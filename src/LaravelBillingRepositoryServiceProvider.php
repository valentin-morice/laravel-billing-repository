<?php

namespace ValentinMorice\LaravelBillingRepository;

use Illuminate\Contracts\Support\DeferrableProvider;
use Spatie\LaravelPackageTools\Exceptions\InvalidPackage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use ValentinMorice\LaravelBillingRepository\Commands\DeployCommand;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderAdapterInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\PriceService as StripePriceService;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\ProductService as StripeProductService;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeAdapter;

class LaravelBillingRepositoryServiceProvider extends PackageServiceProvider implements DeferrableProvider
{
    /**
     * @throws InvalidPackage
     */
    public function register(): void
    {
        parent::register();

        // Register provider adapter
        $this->registerProviderBinding(ProviderAdapterInterface::class, [
            'stripe' => fn () => new StripeAdapter,
        ]);

        // Bind the client interface to resolve from the adapter
        $this->app->singleton(ProviderClientInterface::class, function ($app) {
            return $app->make(ProviderAdapterInterface::class)->client();
        });

        $this->registerProviderBinding(ProductServiceInterface::class, [
            'stripe' => fn ($app) => $app->make(StripeProductService::class),
        ]);

        $this->registerProviderBinding(PriceServiceInterface::class, [
            'stripe' => fn ($app) => $app->make(StripePriceService::class),
        ]);
    }

    /**
     * Register a provider-based binding
     *
     * @param  string  $interface  The interface to bind
     * @param  array<string, callable>  $implementations  Map of provider name to factory callable
     */
    private function registerProviderBinding(string $interface, array $implementations): void
    {
        $this->app->singleton($interface, function ($app) use ($implementations) {
            $provider = config('billing.provider');

            if ($provider === null) {
                throw new \RuntimeException(
                    'Billing provider not configured. Please publish the config file using: '.
                    'php artisan vendor:publish --tag=laravel-billing-repository-config'
                );
            }

            if (! isset($implementations[$provider])) {
                $available = implode(', ', array_keys($implementations));
                throw new \InvalidArgumentException(
                    "Unknown billing provider: '{$provider}'. Available providers: {$available}"
                );
            }

            return $implementations[$provider]($app);
        });
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-billing-repository')
            ->hasConfigFile('billing')
            ->hasMigrations([
                'create_billing_products_table',
                'create_billing_prices_table',
            ])
            ->hasCommand(DeployCommand::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ProviderAdapterInterface::class,
            ProviderClientInterface::class,
            ProductServiceInterface::class,
            PriceServiceInterface::class,
        ];
    }
}
