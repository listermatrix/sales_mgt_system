<?php

namespace App\Services\Order\Repositories;

use App\Services\Order\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    /**
     * Get all orders
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Find order by ID
     *
     * @param int $id
     * @return Order|null
     */
    public function find(int $id): ?Order;

    /**
     * Create a new order with items
     *
     * @param array $data
     * @return Order
     */
    public function create(array $data): Order;

    /**
     * Update order status
     *
     * @param int $id
     * @param string $status
     * @return Order|null
     */
    public function updateStatus(int $id, string $status): ?Order;

    /**
     * Get orders by customer ID
     *
     * @param int $customerId
     * @return Collection
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get orders by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;
}
