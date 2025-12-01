<?php

namespace ValentinMorice\LaravelStripeRepository\Contracts;

interface ProductResourceInterface
{
    public function create(string $name, ?string $description = null): string;

    public function retrieve(string $productId): object;
}
