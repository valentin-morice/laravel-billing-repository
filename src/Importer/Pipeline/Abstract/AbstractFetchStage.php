<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract;

use Closure;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;

abstract class AbstractFetchStage extends AbstractImportStage
{
    public function handle(ImportContext $context, Closure $next): mixed
    {
        $this->fetch($context);

        return $next($context);
    }

    abstract protected function fetch(ImportContext $context): void;
}
