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
            ->hasMigration('create_laravel_stripe_repository_table')
            ->hasCommand(LaravelStripeRepositoryCommand::class);
    }
}
