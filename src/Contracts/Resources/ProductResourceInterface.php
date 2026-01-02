<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts\Resources;

interface ProductResourceInterface
{
    public function create(string $name, ?string $description = null): string;

    public function retrieve(string $productId): object;

    public function update(string $productId, array $params): object;

    public function archive(string $productId): object;

    /**
     * List all products with pagination support
     *
     * @return iterable<object> Iterator of product objects from provider
     */
    public function all(): iterable;
}
