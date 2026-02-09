<?php

namespace App\Services\Payment\Services;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Models\Payment;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paystack Payment Gateway Service
 */
class PaystackGateway implements PaymentGatewayInterface
{
    /**
     * Paystack API base URL
     */
    private const API_URL = 'https://api.paystack.co';

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
        $this->secretKey = config('payment.gateways.paystack.secret_key');
        $this->publicKey = config('payment.gateways.paystack.public_key');
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
            $response = Http::withToken($this->secretKey)
                ->post(self::API_URL . '/transaction/initialize', [
                    'amount' => $payment->amount * 100, // Paystack uses kobo
                    'email' => $payment->order->customer->email ?? config('app.default_email'),
                    'currency' => $payment->currency,
                    'reference' => $this->generateReference(),
                    'metadata' => [
                        'order_id' => $payment->order_id,
                        'payment_id' => $payment->id,
                    ],
                    'callback_url' => route('payment.callback', ['gateway' => 'paystack']),
                ]);

            if ($response->successful() && $response->json()['status']) {
                $data = $response->json()['data'];

                return [
                    'success' => true,
                    'reference' => $data['reference'],
                    'authorization_url' => $data['authorization_url'],
                    'access_code' => $data['access_code'],
                    'public_key' => $this->publicKey,
                    'gateway' => 'paystack',
                ];
            }

            throw new \RuntimeException('Paystack API error: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Paystack initiation failed', [
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
            $response = Http::withToken($this->secretKey)
                ->get(self::API_URL . '/transaction/verify/' . $reference);

            if ($response->successful() && $response->json()['status']) {
                $data = $response->json()['data'];

                return [
                    'success' => true,
                    'status' => $this->mapPaystackStatus($data['status']),
                    'transaction_id' => $data['id'],
                    'reference' => $data['reference'],
                    'amount' => $data['amount'] / 100,
                    'metadata' => $data['metadata'] ?? [],
                ];
            }

            throw new \RuntimeException('Paystack verification failed');

        } catch (\Exception $e) {
            Log::error('Paystack verification failed', [
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
            $response = Http::withToken($this->secretKey)
                ->post(self::API_URL . '/refund', [
                    'transaction' => $payment->transaction_id,
                    'amount' => $amount ? ($amount * 100) : null,
                ]);

            if ($response->successful() && $response->json()['status']) {
                return [
                    'success' => true,
                    'refund_id' => $response->json()['data']['id'],
                    'amount' => $amount ?? $payment->amount,
                ];
            }

            throw new \Exception('Paystack refund failed');

        } catch (\Exception $e) {
            Log::error('Paystack refund failed', [
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
        return 'paystack';
    }

    /**
     * Generate unique payment reference
     *
     * @return string
     */
    private function generateReference(): string
    {
        return 'PAY_' . strtoupper(uniqid() . bin2hex(random_bytes(4)));
    }

    /**
     * Map Paystack status to our payment status
     *
     * @param string $paystackStatus
     * @return string
     */
    private function mapPaystackStatus(string $paystackStatus): string
    {
        return match ($paystackStatus) {
            'success' => PaymentStatus::COMPLETED->value,
            'pending' => PaymentStatus::PENDING->value,
            default => PaymentStatus::FAILED->value,
        };
    }
}
