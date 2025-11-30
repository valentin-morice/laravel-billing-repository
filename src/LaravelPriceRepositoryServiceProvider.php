<?php

namespace ValentinMorice\LaravelPriceRepository;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use ValentinMorice\LaravelPriceRepository\Commands\LaravelPriceRepositoryCommand;

class LaravelPriceRepositoryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-price-repository')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_price_repository_table')
            ->hasCommand(LaravelPriceRepositoryCommand::class);
    }
}
