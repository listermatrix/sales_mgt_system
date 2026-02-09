<?php

namespace App\Services\Order\Repositories;

use App\Services\Order\Models\Order;
use App\Services\Order\Models\OrderItem;
use App\Enums\OrderStatus;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @var Order
     */
    protected Order $model;

    /**
     * @var OrderItem
     */
    protected OrderItem $orderItemModel;

    /**
     * OrderRepository constructor.
     *
     * @param Order $model
     * @param OrderItem $orderItemModel
     */
    public function __construct(Order $model, OrderItem $orderItemModel)
    {
        $this->model = $model;
        $this->orderItemModel = $orderItemModel;
    }

    /**
     * Get all orders with items
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->model->with('items')->latest()->get();
    }

    /**
     * Find order by ID with items
     *
     * @param int $id
     * @return Order|null
     */
    public function find(int $id): ?Order
    {
        return $this->model->with('items')->find($id);
    }

    /**
     * Create a new order with items
     *
     * @param array $data
     * @return Order
     * @throws Exception
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Create the order
            $order = $this->model->create([
                'customer_id' => $data['customer_id'],
                'total_amount' => 0,
                'status' => OrderStatus::PENDING,
            ]);

            $totalAmount = 0;

            // Create order items
            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];

                $this->orderItemModel->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            // Update order total
            $order->update(['total_amount' => $totalAmount]);

            return $order->load('items');
        });
    }

    /**
     * Update order status
     *
     * @param int $id
     * @param string $status
     * @return Order|null
     */
    public function updateStatus(int $id, string $status): ?Order
    {
        $order = $this->find($id);

        if ($order) {
            $order->update(['status' => OrderStatus::from($status)]);
            return $order->fresh();
        }

        return null;
    }

    /**
     * Get orders by customer ID
     *
     * @param int $customerId
     * @return Collection
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->with('items')
            ->where('customer_id', $customerId)
            ->latest()
            ->get();
    }

    /**
     * Get orders by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->with('items')
            ->where('status', $status)
            ->latest()
            ->get();
    }
}
