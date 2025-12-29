<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\Provider;

/**
 * Exception for authentication/authorization errors
 * These are NOT retryable
 */
class ProviderAuthenticationException extends ProviderException
{
    public function isRetryable(): bool
    {
        return false;
    }
}
