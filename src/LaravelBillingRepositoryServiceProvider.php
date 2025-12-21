<?php

namespace ValentinMorice\LaravelBillingRepository;

use Spatie\LaravelPackageTools\Exceptions\InvalidPackage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Stripe\Stripe;
use ValentinMorice\LaravelBillingRepository\Commands\DeployCommand;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderAdapter;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeAdapter;

class LaravelBillingRepositoryServiceProvider extends PackageServiceProvider
{
    /**
     * @throws InvalidPackage
     */
    public function register(): void
    {
        parent::register();

        // Register provider adapter based on config
        $this->app->singleton(ProviderAdapter::class, function ($app) {
            $provider = config('billing.provider', 'stripe');

            return match ($provider) {
                'stripe' => new StripeAdapter(),
                default => throw new \InvalidArgumentException("Unknown billing provider: {$provider}"),
            };
        });

        // Bind the client interface to resolve from the adapter
        $this->app->singleton(ProviderClientInterface::class, function ($app) {
            return $app->make(ProviderAdapter::class)->client();
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
