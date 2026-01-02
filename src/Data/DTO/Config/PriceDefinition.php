<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Config;

readonly class PriceDefinition
{
    public function __construct(
        public int $amount,
        public string $currency = 'eur',
        public ?RecurringConfig $recurring = null,
        public ?string $nickname = null,
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

        return $array;
    }
}
