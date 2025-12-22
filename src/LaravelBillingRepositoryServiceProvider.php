<?php

namespace ValentinMorice\LaravelBillingRepository;

use Spatie\LaravelPackageTools\Exceptions\InvalidPackage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Stripe\Stripe;
use ValentinMorice\LaravelBillingRepository\Commands\DeployCommand;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderAdapterInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\PriceService as StripePriceService;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\ProductService as StripeProductService;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeAdapter;

class LaravelBillingRepositoryServiceProvider extends PackageServiceProvider
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

        // Register service implementations
        $this->registerProviderBinding(ProductServiceInterface::class, [
            'stripe' => fn ($app) => new StripeProductService($app->make(ProviderClientInterface::class)),
        ]);

        $this->registerProviderBinding(PriceServiceInterface::class, [
            'stripe' => fn ($app) => new StripePriceService($app->make(ProviderClientInterface::class)),
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
            $provider = config('billing.provider') ?? 'stripe';

            if (! isset($implementations[$provider])) {
                throw new \InvalidArgumentException("Unknown billing provider: {$provider}");
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

    public function packageBooted(): void
    {
        $apiKey = config('billing.api_key');

        if ($apiKey) {
            Stripe::setApiKey($apiKey);
        }
    }
}
