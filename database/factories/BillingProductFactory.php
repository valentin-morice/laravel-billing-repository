<?php

namespace ValentinMorice\LaravelBillingRepository\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

/**
 * @extends Factory<BillingProduct>
 */
class BillingProductFactory extends Factory
{
    protected $model = BillingProduct::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'provider_id' => 'prod_'.fake()->unique()->numerify('##########'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'active' => true,
        ];
    }

    /**
     * Indicate that the product is archived/inactive.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the product has no description.
     */
    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }
}
