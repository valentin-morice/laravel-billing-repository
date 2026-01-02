<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\IO;

use ValentinMorice\LaravelBillingRepository\Exceptions\BillingException;

class ConfigurationException extends BillingException
{
    public static function configFileNotFound(): self
    {
        return new self(
            'Config file not found. Please publish the config first: '.
            'php artisan vendor:publish --tag=laravel-billing-repository-config'
        );
    }

    public static function providerNotConfigured(): self
    {
        return new self(
            'Billing provider not configured. Please publish the config file using: '.
            'php artisan vendor:publish --tag=laravel-billing-repository-config'
        );
    }

    public static function unknownProvider(string $provider, array $availableProviders): self
    {
        $available = implode(', ', $availableProviders);

        return new self(
            "Unknown billing provider: '{$provider}'. Available providers: {$available}"
        );
    }

    public static function missingProductsKey(): self
    {
        return new self(
            'Could not find products array in config/billing.php. '.
            'Please ensure the config file has a \'products\' key.'
        );
    }

    public static function invalidReturnType(): self
    {
        return new self('Config file must return an array');
    }

    public static function noReturnStatement(): self
    {
        return new self('No return statement found in config file');
    }
}
