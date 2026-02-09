<?php

namespace App\Services\Payment\Services;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Models\Payment;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Log;

/**
 * Payment Service
 *
 * Manages payment processing across different gateways
 */
class PaymentService
{
    /**
     * Get payment gateway instance
     *
     * @param PaymentGateway $gateway
     * @return PaymentGatewayInterface
     * @throws \Exception
     */
    public function gateway(PaymentGateway $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            PaymentGateway::STRIPE => app(StripeGateway::class),
            PaymentGateway::PAYPAL => app(PayPalGateway::class),
            PaymentGateway::PAYSTACK => app(PaystackGateway::class),
        };
    }

    /**
     * Process a payment
     *
     * @param Payment $payment
     * @return array
     */
    public function processPayment(Payment $payment): array
    {
        try {
            $gateway = $this->gateway($payment->gateway);

            // Update status to processing
            $payment->update(['status' => PaymentStatus::PROCESSING]);

            // Initiate payment with gateway
            $result = $gateway->initiate($payment);

            if ($result['success']) {
                // Store gateway reference
                $payment->update([
                    'metadata' => array_merge(
                        $payment->metadata ?? [],
                        ['gateway_response' => $result]
                    ),
                ]);

                Log::info('Payment initiated successfully', [
                    'payment_id' => $payment->id,
                    'gateway' => $gateway->getName(),
                    'reference' => $result['reference'] ?? null,
                ]);
            } else {
                $payment->markAsFailed($result['error'] ?? 'Unknown error');
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a payment
     *
     * @param Payment $payment
     * @param string $reference
     * @return array
     */
    public function verifyPayment(Payment $payment, string $reference): array
    {
        try {
            $gateway = $this->gateway($payment->gateway);

            $result = $gateway->verify($reference);

            if ($result['success']) {
                if ($result['status'] === PaymentStatus::COMPLETED->value) {
                    $payment->markAsCompleted($result['transaction_id']);

                    Log::info('Payment verified successfully', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $result['transaction_id'],
                    ]);
                } else {
                    $payment->update(['status' => PaymentStatus::from($result['status'])]);
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund a payment
     *
     * @param Payment $payment
     * @param float|null $amount
     * @return array
     */
    public function refundPayment(Payment $payment, ?float $amount = null): array
    {
        try {
            if (!$payment->canBeRefunded()) {
                throw new \Exception('Payment cannot be refunded');
            }

            $gateway = $this->gateway($payment->gateway);

            $result = $gateway->refund($payment, $amount);

            if ($result['success']) {
                if ($amount && $amount < $payment->amount) {
                    $payment->update(['status' => PaymentStatus::PARTIALLY_REFUNDED]);
                } else {
                    $payment->markAsRefunded();
                }

                Log::info('Payment refunded successfully', [
                    'payment_id' => $payment->id,
                    'refund_amount' => $result['amount'],
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Payment refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment refund failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available payment gateways
     *
     * @return array
     */
    public function getAvailableGateways(): array
    {
        return collect(PaymentGateway::cases())
            ->filter(fn($gateway) => $gateway->isEnabled())
            ->map(fn($gateway) => [
                'value' => $gateway->value,
                'label' => $gateway->label(),
                'currency' => $gateway->currency(),
            ])
            ->values()
            ->toArray();
    }
}
