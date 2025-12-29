<?php

use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderAuthenticationException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderConnectionException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderInvalidRequestException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderRateLimitException;
use ValentinMorice\LaravelBillingRepository\Stripe\Concerns\RetriesStripeRequests;

beforeEach(function () {
    $this->testClass = new class
    {
        use RetriesStripeRequests;

        public int $attempts = 0;

        public function executeRetryable(callable $callback): mixed
        {
            return $this->retryable($callback);
        }
    };
});

it('succeeds on first attempt when no exception thrown', function () {
    $result = $this->testClass->executeRetryable(function () {
        $this->testClass->attempts++;

        return 'success';
    });

    expect($result)->toBe('success')
        ->and($this->testClass->attempts)->toBe(1);
});

it('retries on rate limit exception and eventually succeeds', function () {
    $result = $this->testClass->executeRetryable(function () {
        $this->testClass->attempts++;

        if ($this->testClass->attempts < 3) {
            throw new RateLimitException('Rate limit exceeded');
        }

        return 'success after retry';
    });

    expect($result)->toBe('success after retry')
        ->and($this->testClass->attempts)->toBe(3);
});

it('retries on connection exception and eventually succeeds', function () {
    $result = $this->testClass->executeRetryable(function () {
        $this->testClass->attempts++;

        if ($this->testClass->attempts < 2) {
            throw new ApiConnectionException('Connection failed');
        }

        return 'connected';
    });

    expect($result)->toBe('connected')
        ->and($this->testClass->attempts)->toBe(2);
});

it('throws provider authentication exception without retry on authentication error', function () {
    $this->testClass->executeRetryable(function () {
        $this->testClass->attempts++;
        throw new AuthenticationException('Invalid API key');
    });
})->throws(ProviderAuthenticationException::class, 'Stripe authentication failed');

it('only attempts once for authentication errors', function () {
    try {
        $this->testClass->executeRetryable(function () {
            $this->testClass->attempts++;
            throw new AuthenticationException('Invalid API key');
        });
    } catch (ProviderAuthenticationException $e) {
        // Expected
    }

    expect($this->testClass->attempts)->toBe(1);
});

it('throws provider invalid request exception without retry on invalid request', function () {
    $this->testClass->executeRetryable(function () {
        throw new InvalidRequestException('Invalid parameter');
    });
})->throws(ProviderInvalidRequestException::class, 'Invalid Stripe request');

it('throws provider rate limit exception after max retries', function () {
    $this->testClass->executeRetryable(function () {
        $this->testClass->attempts++;
        throw new RateLimitException('Rate limit exceeded');
    });
})->throws(ProviderRateLimitException::class);

it('attempts 3 retries for retryable errors', function () {
    try {
        $this->testClass->executeRetryable(function () {
            $this->testClass->attempts++;
            throw new RateLimitException('Rate limit exceeded');
        });
    } catch (ProviderRateLimitException $e) {
        // Expected
    }

    expect($this->testClass->attempts)->toBe(3);
});

it('throws provider connection exception after max retries on connection error', function () {
    $this->testClass->executeRetryable(function () {
        throw new ApiConnectionException('Connection failed');
    });
})->throws(ProviderConnectionException::class, 'Stripe connection error');

it('preserves provider error context in exception', function () {
    try {
        $this->testClass->executeRetryable(function () {
            throw new AuthenticationException('API key invalid');
        });
    } catch (ProviderAuthenticationException $e) {
        expect($e->providerName)->toBe('stripe')
            ->and($e->providerError)->toBeInstanceOf(AuthenticationException::class)
            ->and($e->getMessage())->toContain('Stripe authentication failed');
    }
});

it('identifies retryable exceptions correctly', function () {
    try {
        $this->testClass->executeRetryable(function () {
            throw new RateLimitException('Rate limit');
        });
    } catch (ProviderRateLimitException $e) {
        expect($e->isRetryable())->toBeTrue();
    }

    try {
        $this->testClass->executeRetryable(function () {
            throw new ApiConnectionException('Connection error');
        });
    } catch (ProviderConnectionException $e) {
        expect($e->isRetryable())->toBeTrue();
    }
});

it('identifies non-retryable exceptions correctly', function () {
    try {
        $this->testClass->executeRetryable(function () {
            throw new AuthenticationException('Auth error');
        });
    } catch (ProviderAuthenticationException $e) {
        expect($e->isRetryable())->toBeFalse();
    }

    try {
        $this->testClass->executeRetryable(function () {
            throw new InvalidRequestException('Invalid request');
        });
    } catch (ProviderInvalidRequestException $e) {
        expect($e->isRetryable())->toBeFalse();
    }
});
