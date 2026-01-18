<?php

namespace ValentinMorice\LaravelBillingRepository;

use Illuminate\Contracts\Support\DeferrableProvider;
use PhpParser\BuilderFactory;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Spatie\LaravelPackageTools\Exceptions\InvalidPackage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use ValentinMorice\LaravelBillingRepository\Commands\DeployCommand;
use ValentinMorice\LaravelBillingRepository\Commands\ImportCommand;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderAdapterInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderFeatureExtractorInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\PriceServiceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Services\ProductServiceInterface;
use ValentinMorice\LaravelBillingRepository\Data\Enum\Stripe\ImmutablePriceFields;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Price\DetectPriceChangesStage;
use ValentinMorice\LaravelBillingRepository\Exceptions\IO\ConfigurationException;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\PriceService as StripePriceService;
use ValentinMorice\LaravelBillingRepository\Stripe\Services\ProductService as StripeProductService;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeAdapter;
use ValentinMorice\LaravelBillingRepository\Stripe\StripeFeatureExtractor;

class LaravelBillingRepositoryServiceProvider extends PackageServiceProvider implements DeferrableProvider
{
    /**
     * Immutable fields class map by provider
     *
     * @var array<string, class-string>
     */
    private const IMMUTABLE_FIELDS_MAP = [
        'stripe' => ImmutablePriceFields::class,
    ];

    /**
     * @throws InvalidPackage
     */
    public function register(): void
    {
        parent::register();

        $this->registerProviderBinding(ProviderAdapterInterface::class, [
            'stripe' => fn () => new StripeAdapter,
        ]);

        $this->app->singleton(ProviderClientInterface::class, function ($app) {
            return $app->make(ProviderAdapterInterface::class)->client();
        });

        $this->registerProviderBinding(ProviderFeatureExtractorInterface::class, [
            'stripe' => fn () => new StripeFeatureExtractor,
        ]);

        $this->registerProviderBinding(ProductServiceInterface::class, [
            'stripe' => fn ($app) => $app->make(StripeProductService::class),
        ]);

        $this->registerProviderBinding(PriceServiceInterface::class, [
            'stripe' => fn ($app) => $app->make(StripePriceService::class),
        ]);

        // Bind immutable fields class based on provider for classes that need it
        $this->registerImmutableFieldsBinding(DetectPriceChangesStage::class);
        $this->registerImmutableFieldsBinding(StripePriceService::class);

        $this->app->singleton(Parser::class, function () {
            return (new ParserFactory)->createForNewestSupportedVersion();
        });

        $this->app->singleton(BuilderFactory::class);
        $this->app->singleton(NodeFinder::class);
    }

    /**
     * Register immutable fields class binding for a specific class
     *
     * @param  class-string  $targetClass
     */
    private function registerImmutableFieldsBinding(string $targetClass): void
    {
        $this->app->when($targetClass)
            ->needs('$immutableFieldsClass')
            ->give(function () {
                $provider = config('billing.provider', 'stripe');

                if (! isset(self::IMMUTABLE_FIELDS_MAP[$provider])) {
                    throw ConfigurationException::unknownProvider($provider, array_keys(self::IMMUTABLE_FIELDS_MAP));
                }

                return self::IMMUTABLE_FIELDS_MAP[$provider];
            });
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
                throw ConfigurationException::providerNotConfigured();
            }

            if (! isset($implementations[$provider])) {
                throw ConfigurationException::unknownProvider($provider, array_keys($implementations));
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
                'create_stripe_product_features_table',
                'create_stripe_price_features_table',
            ])
            ->hasCommands([
                DeployCommand::class,
                ImportCommand::class,
            ]);
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
            ProviderFeatureExtractorInterface::class,
            ProductServiceInterface::class,
            PriceServiceInterface::class,
        ];
    }
}
