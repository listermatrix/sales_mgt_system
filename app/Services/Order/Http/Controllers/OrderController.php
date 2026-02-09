<?php

namespace App\Services\Order\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Services\Order\Http\Requests\StoreOrderRequest;
use App\Services\Order\Http\Requests\UpdateOrderStatusRequest;
use App\Services\Order\Http\Resources\OrderResource;
use App\Services\Order\Notifications\OrderConfirmationMail;
use App\Services\Order\Repositories\OrderRepositoryInterface;
use App\Services\Order\Events\OrderPlaced;
use App\Services\Product\Repositories\ProductRepositoryInterface;
use App\Traits\ApiResponse;
use App\Constants\ErrorCode;
use App\Enums\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Order Controller
 *
 * Handles order operations with email notifications
 */
class OrderController extends Controller
{
    use ApiResponse;

    /**
     * OrderController constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private OrderRepositoryInterface   $orderRepository,
        private ProductRepositoryInterface $productRepository
    )
    {
    }

    /**
     * Display a listing of orders.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $orders = $this->orderRepository->all();

        return $this->successResponse(
            OrderResource::collection($orders),
            'Orders retrieved successfully'
        );
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
                    return $this->notFoundResponse(
                        "Product with ID {$item['product_id']} not found",
                        ErrorCode::PRODUCT_NOT_FOUND
                    );
                }

                // Check if product has enough stock
                if (!$product->hasStock($item['quantity'])) {
                    return $this->errorResponse(
                        "Insufficient stock for product: {$product->name}. Available: {$product->stock_quantity}",
                        ErrorCode::INSUFFICIENT_STOCK
                    );
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
                    $product?->decreaseStock($item->quantity);
                }

                return $order;
            });

            // Load relationships for response
            $order->load(['items.product', 'customer']);

            // Dispatch order placed event
            event(new OrderPlaced($order));

            // Send order confirmation email
            try {
                Mail::to($order->customer->email)
                    ->queue(new OrderConfirmationMail($order));

                Log::info('Order confirmation email queued', [
                    'order_id' => $order->id,
                    'customer_email' => $order->customer->email,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to queue order confirmation email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the order if email fails
            }

            return $this->createdResponse(
                new OrderResource($order),
                'Order created successfully'
            );

        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverErrorResponse(
                'Failed to create order: ' . $e->getMessage(),
                ErrorCode::ORDER_CREATION_FAILED
            );
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
            return $this->notFoundResponse(
                ErrorCode::getMessage(ErrorCode::ORDER_NOT_FOUND),
                ErrorCode::ORDER_NOT_FOUND
            );
        }

        // Load relationships
        $order->load(['items.product', 'customer', 'payment']);

        return $this->successResponse(
            new OrderResource($order),
            'Order retrieved successfully'
        );
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
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->notFoundResponse(
                ErrorCode::getMessage(ErrorCode::ORDER_NOT_FOUND),
                ErrorCode::ORDER_NOT_FOUND
            );
        }

        $newStatus = OrderStatus::from($request->status);

        // Check if status transition is valid
        if ($order->status->isFinal()) {
            return $this->errorResponse(
                "Cannot update order status. Order is already in final state: {$order->status->label()}",
                ErrorCode::INVALID_ORDER_STATUS
            );
        }

        // Update status
        $order = $this->orderRepository->updateStatus($id, $request->status);
        $order->load(['items.product', 'customer', 'payment']);

        Log::info('Order status updated', [
            'order_id' => $order->id,
            'new_status' => $newStatus->value,
        ]);

        return $this->successResponse(
            new OrderResource($order),
            'Order status updated successfully'
        );
    }
}
