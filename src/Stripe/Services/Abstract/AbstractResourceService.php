<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Services\Abstract;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Deployer\Actions\DetectChangesAction;

/**
 * @template TModel of Model
 */
abstract class AbstractResourceService
{
    public function __construct(
        protected ProviderClientInterface $client,
        protected DetectChangesAction $detectChanges,
    ) {}

    /**
     * Common archiveRemoved logic
     * Query for removed resources, archive them, and return the list
     *
     * @param  Collection<int, TModel>  $removedResources
     * @return array<TModel>
     */
    protected function archiveRemovedResources(Collection $removedResources): array
    {
        $archived = [];

        foreach ($removedResources as $resource) {
            $this->getArchiveAction()->handle($resource);
            $archived[] = $resource;
        }

        return $archived;
    }

    /**
     * Get the archive action instance for this resource type
     */
    abstract protected function getArchiveAction(): object;
}
