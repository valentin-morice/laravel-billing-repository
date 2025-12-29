<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\Provider;

/**
 * Exception for network/connection errors
 * These are typically retryable
 */
class ProviderConnectionException extends ProviderException
{
    public function isRetryable(): bool
    {
        return true;
    }
}
