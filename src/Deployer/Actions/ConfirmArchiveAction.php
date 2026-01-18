<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Actions;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ChangeSet;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ProductChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Exceptions\Deployer\DeploymentCancelledException;

class ConfirmArchiveAction
{
    /**
     * Confirm archiving of products and prices with the user
     *
     * @throws DeploymentCancelledException
     */
    public function handle(Command $command, ChangeSet $changeSet, bool $skipConfirmation = false): void
    {
        $archivedProducts = $this->getArchivedProducts($changeSet);
        $archivedPrices = $this->getArchivedPrices($changeSet);

        if (empty($archivedProducts) && empty($archivedPrices)) {
            return;
        }

        if ($skipConfirmation) {
            return;
        }

        $this->displayArchivedItems($command, $archivedProducts, $archivedPrices);

        if (! $command->confirm('Continue with archiving?', false)) {
            throw new DeploymentCancelledException('Deployment cancelled by user');
        }
    }

    /**
     * Get products that will be archived
     *
     * @return array<ProductChange>
     */
    private function getArchivedProducts(ChangeSet $changeSet): array
    {
        return array_filter(
            $changeSet->productChanges,
            fn (ProductChange $change) => $change->type === ChangeTypeEnum::Archived
        );
    }

    /**
     * Get prices that will be archived
     *
     * @return array<PriceChange>
     */
    private function getArchivedPrices(ChangeSet $changeSet): array
    {
        return array_filter(
            $changeSet->priceChanges,
            fn (PriceChange $change) => $change->type === ChangeTypeEnum::Archived
        );
    }

    /**
     * Display what will be archived to the user
     *
     * @param  array<ProductChange>  $archivedProducts
     * @param  array<PriceChange>  $archivedPrices
     */
    private function displayArchivedItems(Command $command, array $archivedProducts, array $archivedPrices): void
    {
        $command->newLine();
        $command->warn('⚠ The following items will be archived (deactivated):');
        $command->newLine();

        if (! empty($archivedProducts)) {
            $command->line('  <fg=yellow>Products:</>');
            foreach ($archivedProducts as $product) {
                $command->line("    <fg=red>- {$product->productKey}</>");
            }
        }

        if (! empty($archivedPrices)) {
            $command->line('  <fg=yellow>Prices:</>');
            foreach ($archivedPrices as $price) {
                $command->line("    <fg=red>- {$price->productKey}.{$price->priceKey}</>");
            }
        }

        $command->newLine();
    }
}
