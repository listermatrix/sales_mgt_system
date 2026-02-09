<?php

namespace App\Services\Product\Repositories;

use App\Services\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @var Product
     */
    protected $model;

    /**
     * ProductRepository constructor.
     *
     * @param Product $model
     */
    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    /**
     * Get all products
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->model->latest()->get();
    }

    /**
     * Find product by ID
     *
     * @param int $id
     * @return Product|null
     */
    public function find(int $id): ?Product
    {
        return $this->model->find($id);
    }

    /**
     * Create a new product
     *
     * @param array $data
     * @return Product
     */
    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    /**
     * Update a product
     *
     * @param int $id
     * @param array $data
     * @return Product|null
     */
    public function update(int $id, array $data): ?Product
    {
        $product = $this->find($id);

        if ($product) {
            $product->update($data);
            return $product->fresh();
        }

        return null;
    }

    /**
     * Delete a product
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $product = $this->find($id);

        if ($product) {
            return $product->delete();
        }

        return false;
    }

    /**
     * Find product by SKU
     *
     * @param string $sku
     * @return Product|null
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    /**
     * Get products with low stock
     *
     * @param int $threshold
     * @return Collection
     */
    public function getLowStock(int $threshold = 10): Collection
    {
        return $this->model->where('stock_quantity', '<=', $threshold)
            ->where('stock_quantity', '>', 0)
            ->get();
    }
}
