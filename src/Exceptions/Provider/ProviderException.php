<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\Provider;

use ValentinMorice\LaravelBillingRepository\Exceptions\BillingException;

/**
 * Base exception for all provider API errors
 */
class ProviderException extends BillingException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?string $providerName = null,
        public readonly mixed $providerError = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Check if this error is retryable
     */
    public function isRetryable(): bool
    {
        return false;
    }
}
