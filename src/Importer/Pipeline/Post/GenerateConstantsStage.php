<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Post;

use Closure;
use ValentinMorice\LaravelBillingRepository\ConstantGenerator\ConstantGeneratorService;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract\AbstractImportStage;

class GenerateConstantsStage extends AbstractImportStage
{
    public function __construct(
        protected ConstantGeneratorService $generator,
    ) {}

    public function handle(ImportContext $context, Closure $next): mixed
    {
        if ($context->shouldGenerateConfig) {
            $this->generator->generate();
        }

        return $next($context);
    }
}
