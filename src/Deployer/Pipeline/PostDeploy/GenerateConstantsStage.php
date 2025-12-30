<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\PostDeploy;

use ValentinMorice\LaravelBillingRepository\ConstantGenerator\ConstantGeneratorService;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\DeployContext;
use ValentinMorice\LaravelBillingRepository\Deployer\Pipeline\Abstract\AbstractProcessStage;

class GenerateConstantsStage extends AbstractProcessStage
{
    public function __construct(
        protected ConstantGeneratorService $generator,
    ) {}

    protected function process(DeployContext $context): void
    {
        $this->generator->generate();
    }
}
