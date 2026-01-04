<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Config;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Stripe\StripePriceFeatures;

readonly class PriceDefinition
{
    public function __construct(
        public int $amount,
        public string $currency = 'eur',
        public ?RecurringConfig $recurring = null,
        public ?string $nickname = null,
        public ?array $metadata = null,
        public ?int $trialPeriodDays = null,
        public ?StripePriceFeatures $stripe = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $recurring = null;
        if (isset($data['recurring'])) {
            $recurring = RecurringConfig::fromArray($data['recurring']);
        }

        return new self(
            amount: $data['amount'],
            currency: $data['currency'] ?? 'eur',
            recurring: $recurring,
            nickname: $data['nickname'] ?? null,
            metadata: $data['metadata'] ?? null,
            trialPeriodDays: $data['trial_period_days'] ?? null,
            stripe: StripePriceFeatures::fromArray($data),
        );
    }

    public function toArray(): array
    {
        $array = [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];

        if ($this->recurring !== null) {
            $array['recurring'] = $this->recurring->toArray();
        }
        if ($this->nickname !== null) {
            $array['nickname'] = $this->nickname;
        }
        if ($this->metadata !== null) {
            $array['metadata'] = $this->metadata;
        }
        if ($this->trialPeriodDays !== null) {
            $array['trial_period_days'] = $this->trialPeriodDays;
        }
        if ($this->stripe !== null) {
            $array = array_merge($array, $this->stripe->toArray());
        }

        return $array;
    }
}
