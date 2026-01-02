<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract;

use Closure;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;

abstract class AbstractPersistStage extends AbstractImportStage
{
    public function handle(ImportContext $context, Closure $next): mixed
    {
        $this->persist($context);

        return $next($context);
    }

    abstract protected function persist(ImportContext $context): void;
}
