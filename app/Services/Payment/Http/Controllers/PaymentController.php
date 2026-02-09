<?php

namespace App\Services\Payment\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Services\Payment\Http\Resources\PaymentResource;
use App\Services\Payment\Notification\PaymentSuccessMail;
use App\Services\Payment\Services\PaymentService;
use App\Services\Payment\Models\Payment;
use App\Services\Order\Models\Order;
use App\Traits\ApiResponse;
use App\Constants\ErrorCode;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Payment Controller
 *
 * Handles payment processing across multiple gateways
 */
class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * PaymentController constructor.
     *
     * @param PaymentService $paymentService
     */
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Get available payment gateways
     *
     * @return JsonResponse
     */
    public function gateways(): JsonResponse
    {
        $gateways = $this->paymentService->getAvailableGateways();

        return $this->successResponse(
            $gateways,
            'Available payment gateways retrieved successfully'
        );
    }

    /**
     * Initiate a payment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'gateway' => ['required', 'string', Rule::in(PaymentGateway::values())],
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
        ]);

        try {
            // Check if order exists and doesn't have a payment
            $order = Order::with('payment')->findOrFail($validated['order_id']);

            if ($order?->payment && $order?->payment->status === PaymentStatus::COMPLETED) {
                return $this->conflictResponse(
                    'Order already has a completed payment',
                    ErrorCode::PAYMENT_ALREADY_PROCESSED
                );
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order?->id,
                'gateway' => PaymentGateway::from($validated['gateway']),
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? config('payment.default_currency', 'USD'),
                'status' => PaymentStatus::PENDING,
            ]);

            // Process payment through gateway
            $result = $this->paymentService->processPayment($payment);

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['message'] ?? 'Payment processing failed',
                    ErrorCode::PAYMENT_FAILED
                );
            }

            // Reload payment
            $payment->refresh();

            return $this->createdResponse(
                array_merge(
                    ['payment' => new PaymentResource($payment)],
                    $result
                ),
                'Payment initiated successfully'
            );

        } catch (\Exception $e) {
            Log::error('Payment initiation failed', [
                'error' => $e->getMessage(),
                'order_id' => $validated['order_id'] ?? null,
            ]);

            return $this->serverErrorResponse(
                'Failed to initiate payment',
                ErrorCode::PAYMENT_FAILED
            );
        }
    }

    /**
     * Get payment details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $payment = Payment::with('order')->find($id);

        if (!$payment) {
            return $this->notFoundResponse(
                'Payment not found',
                ErrorCode::PAYMENT_NOT_FOUND
            );
        }

        return $this->successResponse(
            new PaymentResource($payment),
            'Payment retrieved successfully'
        );
    }

    /**
     * Verify a payment
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
        ]);

        try {
            $payment = Payment::with(['order.customer'])->findOrFail($id);

            $result = $this->paymentService->verifyPayment($payment, $validated['reference']);

            if ($result['success'] && $payment?->isSuccessful()) {
                // Update order status
                $payment?->order->updateStatus(OrderStatus::PROCESSING);

                // Send payment success email
                try {
                    Mail::to($payment?->order->customer->email)
                        ->queue(new PaymentSuccessMail($payment));

                    Log::info('Payment success email queued', [
                        'payment_id' => $payment?->id,
                        'order_id' => $payment?->order_id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to queue payment success email', [
                        'payment_id' => $payment?->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $payment?->refresh();

            return $this->successResponse(
                array_merge(
                    ['payment' => new PaymentResource($payment)],
                    $result
                ),
                'Payment verified successfully'
            );

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverErrorResponse(
                'Failed to verify payment',
                ErrorCode::PAYMENT_VERIFICATION_FAILED
            );
        }
    }

    /**
     * Refund a payment
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $payment = Payment::with('order')->findOrFail($id);

            if (!$payment?->canBeRefunded()) {
                return $this->errorResponse(
                    'Payment cannot be refunded',
                    ErrorCode::PAYMENT_REFUND_FAILED
                );
            }

            $result = $this->paymentService->refundPayment(
                $payment,
                $validated['amount'] ?? null
            );

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['message'] ?? 'Refund failed',
                    ErrorCode::PAYMENT_REFUND_FAILED
                );
            }

            // Update order status if full refund
            if (!isset($validated['amount']) || $validated['amount'] >= $payment?->amount) {
                $payment?->order->updateStatus(OrderStatus::REFUNDED);
            }

            $payment?->refresh();

            return $this->successResponse(
                array_merge(
                    ['payment' => new PaymentResource($payment)],
                    $result
                ),
                'Payment refunded successfully'
            );

        } catch (\Exception $e) {
            Log::error('Payment refund failed', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverErrorResponse(
                'Failed to process refund',
                ErrorCode::PAYMENT_REFUND_FAILED
            );
        }
    }
}
