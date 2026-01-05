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
        $this->generator->generate();
    }
}
