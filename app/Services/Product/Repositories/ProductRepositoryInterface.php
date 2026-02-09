<?php

namespace App\Services\Product\Repositories;

use App\Services\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Get all products
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Find product by ID
     *
     * @param int $id
     * @return Product|null
     */
    public function find(int $id): ?Product;

    /**
     * Create a new product
     *
     * @param array $data
     * @return Product
     */
    public function create(array $data): Product;

    /**
     * Update a product
     *
     * @param int $id
     * @param array $data
     * @return Product|null
     */
    public function update(int $id, array $data): ?Product;

    /**
     * Delete a product
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Find product by SKU
     *
     * @param string $sku
     * @return Product|null
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Get products with low stock
     *
     * @param int $threshold
     * @return Collection
     */
    public function getLowStock(int $threshold = 10): Collection;
}
