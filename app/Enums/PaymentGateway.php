<?php

namespace App\Enums;

/**
 * Payment Gateway Enum
 */
enum PaymentGateway: string
{
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case PAYSTACK = 'paystack';

    /**
     * Get all gateway values
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get gateway display name
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::STRIPE => 'Stripe',
            self::PAYPAL => 'PayPal',
            self::PAYSTACK => 'Paystack',
        };
    }

    /**
     * Get gateway configuration key
     *
     * @return string
     */
    public function configKey(): string
    {
        return 'payment.gateways.' . $this->value;
    }

    /**
     * Check if gateway is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return config($this->configKey() . '.enabled', false);
    }

    /**
     * Get gateway currency
     *
     * @return string
     */
    public function currency(): string
    {
        return config($this->configKey() . '.currency', 'USD');
    }
}
