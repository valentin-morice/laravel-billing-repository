<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Config;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Stripe\StripeProductFeatures;

readonly class ProductDefinition
{
    /**
     * @param  array<string, PriceDefinition>  $prices
     */
    public function __construct(
        public string $name,
        public array $prices,
        public ?string $description = null,
        public ?array $metadata = null,
        public ?StripeProductFeatures $stripe = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $prices = [];
        foreach ($data['prices'] ?? [] as $type => $priceData) {
            $prices[$type] = PriceDefinition::fromArray($priceData);
        }

        return new self(
            name: $data['name'],
            prices: $prices,
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? null,
            stripe: StripeProductFeatures::fromArray($data),
        );
    }

    public function toArray(): array
    {
        $prices = [];
        foreach ($this->prices as $type => $priceDefinition) {
            $prices[$type] = $priceDefinition->toArray();
        }

        $array = [
            'name' => $this->name,
            'prices' => $prices,
        ];

        if ($this->description !== null) {
            $array['description'] = $this->description;
        }
        if ($this->metadata !== null) {
            $array['metadata'] = $this->metadata;
        }
        if ($this->stripe !== null) {
            $array = array_merge($array, $this->stripe->toArray());
        }

        return $array;
    }
}
