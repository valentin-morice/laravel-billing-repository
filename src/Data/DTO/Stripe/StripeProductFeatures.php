<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Stripe;

readonly class StripeProductFeatures
{
    public function __construct(
        public ?string $taxCode = null,
        public ?string $statementDescriptor = null,
    ) {}

    public static function fromArray(array $data): ?self
    {
        // Extract Stripe fields from flat config
        $taxCode = $data['tax_code'] ?? null;
        $statementDescriptor = $data['statement_descriptor'] ?? null;

        // Return null if no Stripe fields present
        if ($taxCode === null && $statementDescriptor === null) {
            return null;
        }

        return new self(
            taxCode: $taxCode,
            statementDescriptor: $statementDescriptor,
        );
    }

    public function toArray(): array
    {
        $array = [];

        if ($this->taxCode !== null) {
            $array['tax_code'] = $this->taxCode;
        }
        if ($this->statementDescriptor !== null) {
            $array['statement_descriptor'] = $this->statementDescriptor;
        }

        return $array;
    }
}
