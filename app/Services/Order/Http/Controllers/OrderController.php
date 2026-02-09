<?php

namespace App\Services\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Order\Events\OrderPlaced;
use App\Services\Order\Http\Requests\StoreOrderRequest;
use App\Services\Order\Http\Requests\UpdateOrderStatusRequest;
use App\Services\Order\Repositories\OrderRepositoryInterface;
use App\Services\Product\Repositories\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * OrderController constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Display a listing of orders.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $orders = $this->orderRepository->all();

        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'Orders retrieved successfully'
        ], 200);
    }

    /**
     * Store a newly created order.
     *
     * @param StoreOrderRequest $request
     * @return JsonResponse
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // Prepare order data with product prices and check stock
            $orderData = [
                'customer_id' => $validatedData['customer_id'],
                'items' => []
            ];

            foreach ($validatedData['items'] as $item) {
                $product = $this->productRepository->find($item['product_id']);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'message' => "Product with ID {$item['product_id']} not found",
                            'code' => 'PRODUCT_NOT_FOUND'
                        ]
                    ], 404);
                }

                // Check if product has enough stock
                if (!$product->hasStock($item['quantity'])) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'message' => "Insufficient stock for product: {$product->name}. Available: {$product->stock_quantity}",
                            'code' => 'INSUFFICIENT_STOCK'
                        ]
                    ], 400);
                }

                $orderData['items'][] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                ];
            }

            // Create order in a transaction
            $order = DB::transaction(function () use ($orderData) {
                $order = $this->orderRepository->create($orderData);

                // Decrease stock for each product
                foreach ($order->items as $item) {
                    $product = $this->productRepository->find($item->product_id);
                    $product->decreaseStock($item->quantity);
                }

                return $order;
            });

            // Dispatch order placed event
            event(new OrderPlaced($order));

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Failed to create order: ' . $e->getMessage(),
                    'code' => 'ORDER_CREATION_FAILED'
                ]
            ], 500);
        }
    }

    /**
     * Display the specified order.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Order not found',
                    'code' => 'ORDER_NOT_FOUND'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order retrieved successfully'
        ], 200);
    }

    /**
     * Update the order status.
     *
     * @param UpdateOrderStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->updateStatus($id, $request->status);

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Order not found',
                    'code' => 'ORDER_NOT_FOUND'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order status updated successfully'
        ], 200);
    }
}
