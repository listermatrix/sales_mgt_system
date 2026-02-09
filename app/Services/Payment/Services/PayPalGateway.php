<?php

namespace App\Services\Payment\Services;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Models\Payment;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Payment Gateway Service
 */
class PayPalGateway implements PaymentGatewayInterface
{
    /**
     * PayPal API base URL
     */
    private string $apiUrl;

    /**
     * @var string
     */
    private string $clientId;

    /**
     * @var string
     */
    private string $clientSecret;

    /**
     * @var string|null
     */
    private ?string $accessToken = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $mode = config('payment.gateways.paypal.mode', 'sandbox');
        $this->apiUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->clientId = config('payment.gateways.paypal.client_id');
        $this->clientSecret = config('payment.gateways.paypal.client_secret');
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
            $this->getAccessToken();

            $response = Http::withToken($this->accessToken)
                ->post($this->apiUrl . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => 'ORDER_' . $payment->order_id,
                            'amount' => [
                                'currency_code' => $payment->currency,
                                'value' => number_format($payment->amount, 2, '.', ''),
                            ],
                            'description' => "Order #{$payment->order_id}",
                        ],
                    ],
                    'application_context' => [
                        'return_url' => route('payment.callback', ['gateway' => 'paypal']),
                        'cancel_url' => route('payment.cancel', ['gateway' => 'paypal']),
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $approvalLink = collect($data['links'])->firstWhere('rel', 'approve')['href'] ?? null;

                return [
                    'success' => true,
                    'reference' => $data['id'],
                    'approval_url' => $approvalLink,
                    'gateway' => 'paypal',
                ];
            }

            throw new \RuntimeException('PayPal API error: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('PayPal initiation failed', [
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
            $this->getAccessToken();

            $response = Http::withToken($this->accessToken)
                ->get($this->apiUrl . '/v2/checkout/orders/' . $reference);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'status' => $this->mapPayPalStatus($data['status']),
                    'transaction_id' => $data['id'],
                    'amount' => $data['purchase_units'][0]['amount']['value'] ?? 0,
                    'metadata' => $data,
                ];
            }

            throw new \RuntimeException('PayPal verification failed');

        } catch (\Exception $e) {
            Log::error('PayPal verification failed', [
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
            $this->getAccessToken();

            // PayPal requires capture ID, which should be stored in metadata
            $captureId = $payment->metadata['capture_id'] ?? null;

            if (!$captureId) {
                throw new \RuntimeException('Capture ID not found for refund');
            }

            $refundData = [];
            if ($amount) {
                $refundData['amount'] = [
                    'currency_code' => $payment->currency,
                    'value' => number_format($amount, 2, '.', ''),
                ];
            }

            $response = Http::withToken($this->accessToken)
                ->post($this->apiUrl . '/v2/payments/captures/' . $captureId . '/refund', $refundData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json()['id'],
                    'amount' => $amount ?? $payment->amount,
                ];
            }

            throw new \RuntimeException('PayPal refund failed');

        } catch (\Exception $e) {
            Log::error('PayPal refund failed', [
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
        return 'paypal';
    }

    /**
     * Get PayPal access token
     *
     * @return void
     * @throws \Exception
     */
    private function getAccessToken(): void
    {
        if ($this->accessToken) {
            return;
        }

        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->apiUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->successful()) {
            $this->accessToken = $response->json()['access_token'];
        } else {
            throw new \RuntimeException('Failed to get PayPal access token');
        }
    }

    /**
     * Map PayPal status to our payment status
     *
     * @param string $paypalStatus
     * @return string
     */
    private function mapPayPalStatus(string $paypalStatus): string
    {
        return match ($paypalStatus) {
            'COMPLETED', 'APPROVED' => PaymentStatus::COMPLETED->value,
            'VOIDED', 'EXPIRED' => PaymentStatus::FAILED->value,
            default => PaymentStatus::PENDING->value,
        };
    }
}
