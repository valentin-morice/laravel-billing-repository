<?php

namespace ValentinMorice\LaravelBillingRepository\Data\DTO\Config;

readonly class RecurringConfig
{
    public function __construct(
        public string $interval,
        public int $intervalCount = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            interval: $data['interval'],
            intervalCount: $data['interval_count'] ?? 1,
        );
    }

    public function toArray(): array
    {
        $array = [
            'interval' => $this->interval,
        ];

        if ($this->intervalCount !== 1) {
            $array['interval_count'] = $this->intervalCount;
        }

        return $array;
    }
}
