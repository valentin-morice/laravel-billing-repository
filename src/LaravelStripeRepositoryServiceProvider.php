<?php

namespace ValentinMorice\LaravelStripeRepository;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use ValentinMorice\LaravelStripeRepository\Commands\LaravelStripeRepositoryCommand;

class LaravelStripeRepositoryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-stripe-repository')
            ->hasConfigFile()
            ->hasMigrations([
                'create_stripe_products_table',
                'create_stripe_prices_table',
            ])
            ->hasCommand(LaravelStripeRepositoryCommand::class);
    }
}
