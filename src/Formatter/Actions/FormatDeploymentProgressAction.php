<?php

namespace ValentinMorice\LaravelBillingRepository\Formatter\Actions;

use Illuminate\Console\Command;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\PriceChange;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Deployer\ProductChange;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;

class FormatDeploymentProgressAction
{
    /**
     * Format and display deployment progress for a single change
     */
    public function handle(Command $command, ProductChange|PriceChange $change): void
    {
        $symbol = '✓';
        $action = match ($change->type) {
            ChangeTypeEnum::Created => 'Created',
            ChangeTypeEnum::Updated => 'Updated',
            ChangeTypeEnum::Archived => 'Archived',
            default => 'Processed',
        };

        if ($change instanceof ProductChange) {
            $name = $change->productKey;
            $providerId = $change->resultProduct !== null ? $change->resultProduct->provider_id : '';
            $message = "{$symbol} {$action} product: {$name}";
        } else {
            $name = "{$change->productKey}.{$change->priceType}";
            $providerId = $change->resultPrice !== null ? $change->resultPrice->provider_id : '';
            $message = "{$symbol} {$action} price: {$name}";
        }

        if ($providerId) {
            $message .= " ({$providerId})";
        }

        $command->info($message);
    }
}
