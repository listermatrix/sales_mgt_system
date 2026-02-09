<?php

namespace App\Services\Payment\Contracts;

use App\Services\Payment\Models\Payment;

/**
 * Payment Gateway Contract
 *
 * Interface that all payment gateways must implement
 */
interface PaymentGatewayInterface
{
    /**
     * Initialize a payment
     *
     * @param Payment $payment
     * @return array
     */
    public function initiate(Payment $payment): array;

    /**
     * Verify a payment
     *
     * @param string $reference
     * @return array
     */
    public function verify(string $reference): array;

    /**
     * Refund a payment
     *
     * @param Payment $payment
     * @param float|null $amount
     * @return array
     */
    public function refund(Payment $payment, ?float $amount = null): array;

    /**
     * Get payment status
     *
     * @param string $reference
     * @return string
     */
    public function getStatus(string $reference): string;

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getName(): string;
}
