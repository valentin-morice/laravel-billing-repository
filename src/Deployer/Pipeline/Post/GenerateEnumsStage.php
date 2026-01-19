<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Post;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractProcessStage;
use ValentinMorice\LaravelBillingRepository\EnumGenerator\EnumGeneratorService;

class GenerateEnumsStage extends AbstractProcessStage
{
    public function __construct(
        protected EnumGeneratorService $generator,
    ) {}

    protected function process(DeployContext $context): void
    {
        $context->command?->info('Generating enums...');

        $this->generator->generate();

        $context->command?->info('✓ ProductKey and PriceKey enums generated');
    }
}
