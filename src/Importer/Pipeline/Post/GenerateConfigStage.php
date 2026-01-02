<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Post;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\Importer\Actions\GenerateConfigFileAction;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract\AbstractImportStage;

class GenerateConfigStage extends AbstractImportStage
{
    public function __construct(
        protected GenerateConfigFileAction $generateConfig,
    ) {}

    /**
     * @throws FileNotFoundException
     */
    public function handle(ImportContext $context, Closure $next): mixed
    {
        // Only run if --generate-config flag is set
        if ($context->shouldGenerateConfig) {
            $this->generateConfig->handle($context);
        }

        return $next($context);
    }
}
