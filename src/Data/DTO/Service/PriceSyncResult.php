<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Service;

use ValentinMorice\LaravelBillingRepository\Data\Enum\ChangeTypeEnum;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

readonly class PriceSyncResult
{
    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        public ChangeTypeEnum $action,
        public BillingPrice $price,
        public ?BillingPrice $oldPrice = null,
        public array $changes = [],
        public bool $wasDuplicated = false,
    ) {}

    /**
     * Factory for created price
     */
    public static function created(BillingPrice $price): self
    {
        return new self(ChangeTypeEnum::Created, $price);
    }

    /**
     * Factory for updated price
     *
     * @param  BillingPrice  $newPrice  The updated price (or new price if archived+created)
     * @param  BillingPrice|null  $oldPrice  The archived price (null for in-place updates)
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public static function updated(
        BillingPrice $newPrice,
        ?BillingPrice $oldPrice,
        array $changes
    ): self {
        return new self(
            ChangeTypeEnum::Updated,
            $newPrice,
            $oldPrice,
            $changes
        );
    }

    /**
     * Factory for duplicated price (kept old price active, created new with different key)
     *
     * @param  BillingPrice  $newPrice  The newly created price with different key
     * @param  BillingPrice  $keptPrice  The original price that was kept active
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public static function duplicated(
        BillingPrice $newPrice,
        BillingPrice $keptPrice,
        array $changes
    ): self {
        return new self(
            ChangeTypeEnum::Created,
            $newPrice,
            $keptPrice,
            $changes,
            wasDuplicated: true
        );
    }

    /**
     * Factory for unchanged price
     */
    public static function unchanged(BillingPrice $price): self
    {
        return new self(ChangeTypeEnum::Unchanged, $price);
    }

    /**
     * Check if the price was created
     */
    public function wasCreated(): bool
    {
        return $this->action === ChangeTypeEnum::Created;
    }

    /**
     * Check if the price was updated
     */
    public function wasUpdated(): bool
    {
        return $this->action === ChangeTypeEnum::Updated;
    }

    /**
     * Check if the price was unchanged
     */
    public function wasUnchanged(): bool
    {
        return $this->action === ChangeTypeEnum::Unchanged;
    }

    /**
     * Check if the price was duplicated (kept old, created new)
     */
    public function wasDuplicated(): bool
    {
        return $this->wasDuplicated;
    }

    /**
     * Check if the price has changes
     */
    public function hasChanges(): bool
    {
        return ! empty($this->changes);
    }
}
