<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Post;

use Closure;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\EnumGenerator\EnumGeneratorService;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract\AbstractImportStage;

class GenerateEnumsStage extends AbstractImportStage
{
    public function __construct(
        protected EnumGeneratorService $generator,
    ) {}

    public function handle(ImportContext $context, Closure $next): mixed
    {
        if ($context->shouldGenerateConfig) {
            $context->command?->info('Generating enums...');

            $this->generator->generate();

            $context->command?->line('✓ ProductKey and PriceKey enums generated');
        }

        return $next($context);
    }
}
