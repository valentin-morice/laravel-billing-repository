<?php

namespace ValentinMorice\LaravelBillingRepository\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

/**
 * @extends Factory<BillingPrice>
 */
class BillingPriceFactory extends Factory
{
    protected $model = BillingPrice::class;

    public function definition(): array
    {
        return [
            'product_id' => BillingProduct::factory(),
            'type' => 'default',
            'provider_id' => 'price_'.fake()->unique()->numerify('##########'),
            'amount' => fake()->numberBetween(500, 50000),
            'currency' => 'eur',
            'recurring' => null,
            'nickname' => null,
            'active' => true,
        ];
    }

    /**
     * Indicate that the price is recurring with monthly interval.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'monthly',
            'recurring' => ['interval' => 'month'],
        ]);
    }

    /**
     * Indicate that the price is recurring with yearly interval.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'yearly',
            'recurring' => ['interval' => 'year'],
        ]);
    }

    /**
     * Set a custom recurring interval.
     */
    public function recurring(string $interval, ?int $intervalCount = null): static
    {
        $recurring = ['interval' => $interval];

        if ($intervalCount !== null) {
            $recurring['interval_count'] = $intervalCount;
        }

        return $this->state(fn (array $attributes) => [
            'recurring' => $recurring,
        ]);
    }

    /**
     * Indicate that the price is archived/inactive.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Set the price currency.
     */
    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }

    /**
     * Set the price amount.
     */
    public function amount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Associate the price with a specific product.
     */
    public function forProduct(BillingProduct $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
}
