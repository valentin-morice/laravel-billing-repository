<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Stripe;

readonly class StripePriceFeatures
{
    public function __construct(
        public ?string $taxBehavior = null,
        public ?string $lookupKey = null,
    ) {}

    public static function fromArray(array $data): ?self
    {
        // Extract Stripe fields from flat config
        $taxBehavior = $data['tax_behavior'] ?? null;
        $lookupKey = $data['lookup_key'] ?? null;

        // Return null if no Stripe fields present
        if ($taxBehavior === null && $lookupKey === null) {
            return null;
        }

        return new self(
            taxBehavior: $taxBehavior,
            lookupKey: $lookupKey,
        );
    }

    public function toArray(): array
    {
        $array = [];

        if ($this->taxBehavior !== null) {
            $array['tax_behavior'] = $this->taxBehavior;
        }
        if ($this->lookupKey !== null) {
            $array['lookup_key'] = $this->lookupKey;
        }

        return $array;
    }
}
