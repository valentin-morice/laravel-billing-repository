<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\Provider;

/**
 * Exception for invalid request parameters
 * These are NOT retryable
 */
class ProviderInvalidRequestException extends ProviderException
{
    public function isRetryable(): bool
    {
        return false;
    }
}
