<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Abstract;

use Illuminate\Database\Eloquent\Model;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;

abstract class AbstractArchiveAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    /**
     * Archive a resource via the provider API
     */
    abstract protected function archiveInProvider(string $providerId): void;

    /**
     * Mark the resource as inactive in the database
     */
    protected function markAsInactive(Model $resource): void
    {
        $resource->update(['active' => false]);
    }
}
