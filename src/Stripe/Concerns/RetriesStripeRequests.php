<?php

/** @noinspection PhpMixedReturnTypeCanBeReducedInspection */

namespace ValentinMorice\LaravelBillingRepository\Stripe\Concerns;

use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Throwable;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderAuthenticationException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderConnectionException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderInvalidRequestException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderRateLimitException;

trait RetriesStripeRequests
{
    /**
     * Execute a Stripe API call with retry logic using Laravel's retry helper
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws ProviderException|Throwable
     */
    protected function retryable(callable $callback): mixed
    {
        try {
            return retry(3, $callback, 100, function (Throwable $e) {
                // Only retry on connection and rate limit errors
                return $e instanceof ApiConnectionException || $e instanceof RateLimitException;
            });
        } catch (ApiErrorException $e) {
            throw $this->mapStripeException($e);
        }
    }

    /**
     * Map Stripe exception to custom exception
     */
    protected function mapStripeException(ApiErrorException $e): ProviderException
    {
        return match (true) {
            $e instanceof AuthenticationException => new ProviderAuthenticationException(
                message: 'Stripe authentication failed: '.$e->getMessage(),
                code: $e->getCode(),
                previous: $e,
                providerName: 'stripe',
                providerError: $e
            ),
            $e instanceof RateLimitException => new ProviderRateLimitException(
                message: 'Stripe rate limit exceeded: '.$e->getMessage(),
                code: $e->getCode(),
                previous: $e,
                providerName: 'stripe',
                providerError: $e,
                retryAfter: null
            ),
            $e instanceof ApiConnectionException => new ProviderConnectionException(
                message: 'Stripe connection error: '.$e->getMessage(),
                code: $e->getCode(),
                previous: $e,
                providerName: 'stripe',
                providerError: $e
            ),
            $e instanceof InvalidRequestException => new ProviderInvalidRequestException(
                message: 'Invalid Stripe request: '.$e->getMessage(),
                code: $e->getCode(),
                previous: $e,
                providerName: 'stripe',
                providerError: $e
            ),
            default => new ProviderException(
                message: 'Stripe API error: '.$e->getMessage(),
                code: $e->getCode(),
                previous: $e,
                providerName: 'stripe',
                providerError: $e
            ),
        };
    }
}
