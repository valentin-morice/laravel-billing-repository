<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract;

use Closure;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;

abstract class AbstractImportStage
{
    abstract public function handle(ImportContext $context, Closure $next): mixed;
}
