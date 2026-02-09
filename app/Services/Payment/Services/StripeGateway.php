<?php

namespace App\Services\Payment\Services;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Models\Payment;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Payment Gateway Service
 */
class StripeGateway implements PaymentGatewayInterface
{
    /**
     * Stripe API base URL
     */
    private const API_URL = 'https://api.stripe.com/v1';

    /**
     * @var string
     */
    private string $secretKey;

    /**
     * @var string
     */
    private string $publicKey;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->secretKey = config('payment.gateways.stripe.secret_key');
        $this->publicKey = config('payment.gateways.stripe.public_key');
    }

    /**
     * Initialize a payment
     *
     * @param Payment $payment
     * @return array
     */
    public function initiate(Payment $payment): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post(self::API_URL . '/payment_intents', [
                    'amount' => $payment->amount * 100, // Stripe uses cents
                    'currency' => strtolower($payment->currency),
                    'description' => "Order #{$payment->order_id}",
                    'metadata' => [
                        'order_id' => $payment->order_id,
                        'payment_id' => $payment->id,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'reference' => $data['id'],
                    'client_secret' => $data['client_secret'],
                    'public_key' => $this->publicKey,
                    'gateway' => 'stripe',
                ];
            }

            throw new \Exception('Stripe API error: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Stripe initiation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment initiation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a payment
     *
     * @param string $reference
     * @return array
     */
    public function verify(string $reference): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get(self::API_URL . '/payment_intents/' . $reference);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'status' => $this->mapStripeStatus($data['status']),
                    'transaction_id' => $data['id'],
                    'amount' => $data['amount'] / 100,
                    'metadata' => $data['metadata'] ?? [],
                ];
            }

            throw new \Exception('Stripe verification failed');

        } catch (\Exception $e) {
            Log::error('Stripe verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification failed',
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
    public function refund(Payment $payment, ?float $amount = null): array
    {
        try {
            $refundAmount = $amount ? ($amount * 100) : ($payment->amount * 100);

            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post(self::API_URL . '/refunds', [
                    'payment_intent' => $payment->transaction_id,
                    'amount' => $refundAmount,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json()['id'],
                    'amount' => $refundAmount / 100,
                ];
            }

            throw new \Exception('Stripe refund failed');

        } catch (\Exception $e) {
            Log::error('Stripe refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Refund failed',
            ];
        }
    }

    /**
     * Get payment status
     *
     * @param string $reference
     * @return string
     */
    public function getStatus(string $reference): string
    {
        $result = $this->verify($reference);

        return $result['status'] ?? PaymentStatus::FAILED->value;
    }

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'stripe';
    }

    /**
     * Map Stripe status to our payment status
     *
     * @param string $stripeStatus
     * @return string
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => PaymentStatus::COMPLETED->value,
            'processing' => PaymentStatus::PROCESSING->value,
            'requires_payment_method', 'requires_confirmation', 'requires_action' => PaymentStatus::PENDING->value,
            default => PaymentStatus::FAILED->value,
        };
    }
}
