<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Service;

use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

readonly class ProductSyncResult
{
    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        public ChangeTypeEnum $action,
        public BillingProduct $product,
        public array $changes = [],
    ) {}

    /**
     * Factory for created product
     */
    public static function created(BillingProduct $product): self
    {
        return new self(ChangeTypeEnum::Created, $product);
    }

    /**
     * Factory for updated product
     */
    public static function updated(BillingProduct $product, array $changes): self
    {
        return new self(ChangeTypeEnum::Updated, $product, $changes);
    }

    /**
     * Factory for unchanged product
     */
    public static function unchanged(BillingProduct $product): self
    {
        return new self(ChangeTypeEnum::Unchanged, $product);
    }

    /**
     * Check if the product was created
     */
    public function wasCreated(): bool
    {
        return $this->action === ChangeTypeEnum::Created;
    }

    /**
     * Check if the product was updated
     */
    public function wasUpdated(): bool
    {
        return $this->action === ChangeTypeEnum::Updated;
    }

    /**
     * Check if the product was unchanged
     */
    public function wasUnchanged(): bool
    {
        return $this->action === ChangeTypeEnum::Unchanged;
    }

    /**
     * Check if the product has changes
     */
    public function hasChanges(): bool
    {
        return ! empty($this->changes);
    }
}
