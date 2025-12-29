<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\Provider;

/**
 * Exception for rate limit errors
 * These are retryable with exponential backoff
 */
class ProviderRateLimitException extends ProviderException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $providerName = null,
        mixed $providerError = null,
        public readonly ?int $retryAfter = null
    ) {
        parent::__construct($message, $code, $previous, $providerName, $providerError);
    }

    public function isRetryable(): bool
    {
        return true;
    }
}
