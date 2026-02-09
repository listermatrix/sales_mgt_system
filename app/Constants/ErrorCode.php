<?php

namespace App\Constants;

/**
 * Application Error Codes
 *
 * Centralized error codes for consistent error handling
 */
class ErrorCode
{
    // General Errors (1000-1099)
    public const GENERAL_ERROR = 'ERR_1000';
    public const VALIDATION_ERROR = 'ERR_1001';
    public const AUTHENTICATION_ERROR = 'ERR_1002';
    public const AUTHORIZATION_ERROR = 'ERR_1003';
    public const RATE_LIMIT_EXCEEDED = 'ERR_1004';
    public const RESOURCE_NOT_FOUND = 'ERR_1005';

    // Customer Errors (2000-2099)
    public const CUSTOMER_NOT_FOUND = 'ERR_2001';
    public const CUSTOMER_ALREADY_EXISTS = 'ERR_2002';
    public const CUSTOMER_CREATION_FAILED = 'ERR_2003';
    public const CUSTOMER_UPDATE_FAILED = 'ERR_2004';
    public const CUSTOMER_DELETE_FAILED = 'ERR_2005';

    // Product Errors (3000-3099)
    public const PRODUCT_NOT_FOUND = 'ERR_3001';
    public const PRODUCT_ALREADY_EXISTS = 'ERR_3002';
    public const PRODUCT_CREATION_FAILED = 'ERR_3003';
    public const PRODUCT_UPDATE_FAILED = 'ERR_3004';
    public const PRODUCT_DELETE_FAILED = 'ERR_3005';
    public const INSUFFICIENT_STOCK = 'ERR_3006';
    public const PRODUCT_OUT_OF_STOCK = 'ERR_3007';

    // Order Errors (4000-4099)
    public const ORDER_NOT_FOUND = 'ERR_4001';
    public const ORDER_CREATION_FAILED = 'ERR_4002';
    public const ORDER_UPDATE_FAILED = 'ERR_4003';
    public const ORDER_CANCELLATION_FAILED = 'ERR_4004';
    public const INVALID_ORDER_STATUS = 'ERR_4005';
    public const ORDER_ALREADY_COMPLETED = 'ERR_4006';
    public const ORDER_ALREADY_CANCELLED = 'ERR_4007';
    public const ORDER_CANNOT_BE_CANCELLED = 'ERR_4008';

    // Payment Errors (5000-5099)
    public const PAYMENT_FAILED = 'ERR_5001';
    public const PAYMENT_GATEWAY_ERROR = 'ERR_5002';
    public const PAYMENT_ALREADY_PROCESSED = 'ERR_5003';
    public const PAYMENT_NOT_FOUND = 'ERR_5004';
    public const INVALID_PAYMENT_AMOUNT = 'ERR_5005';
    public const PAYMENT_VERIFICATION_FAILED = 'ERR_5006';
    public const PAYMENT_REFUND_FAILED = 'ERR_5007';

    // Notification Errors (6000-6099)
    public const EMAIL_SEND_FAILED = 'ERR_6001';
    public const SMS_SEND_FAILED = 'ERR_6002';
    public const NOTIFICATION_FAILED = 'ERR_6003';

    // Report Errors (7000-7099)
    public const REPORT_GENERATION_FAILED = 'ERR_7001';
    public const INVALID_REPORT_TYPE = 'ERR_7002';
    public const INVALID_DATE_RANGE = 'ERR_7003';

    /**
     * Get error message for a given code
     *
     * @param string $code
     * @return string
     */
    public static function getMessage(string $code): string
    {
        return match ($code) {
            self::GENERAL_ERROR => 'An unexpected error occurred',
            self::VALIDATION_ERROR => 'Validation failed',
            self::AUTHENTICATION_ERROR => 'Authentication failed',
            self::AUTHORIZATION_ERROR => 'You are not authorized to perform this action',
            self::RATE_LIMIT_EXCEEDED => 'Too many requests. Please try again later',
            self::RESOURCE_NOT_FOUND => 'The requested resource was not found',

            self::CUSTOMER_NOT_FOUND => 'Customer not found',
            self::CUSTOMER_ALREADY_EXISTS => 'Customer already exists',
            self::CUSTOMER_CREATION_FAILED => 'Failed to create customer',

            self::PRODUCT_NOT_FOUND => 'Product not found',
            self::INSUFFICIENT_STOCK => 'Insufficient product stock',
            self::PRODUCT_OUT_OF_STOCK => 'Product is out of stock',

            self::ORDER_NOT_FOUND => 'Order not found',
            self::ORDER_CREATION_FAILED => 'Failed to create order',
            self::INVALID_ORDER_STATUS => 'Invalid order status',
            self::ORDER_CANNOT_BE_CANCELLED => 'Order cannot be cancelled at this stage',

            self::PAYMENT_FAILED => 'Payment processing failed',
            self::PAYMENT_GATEWAY_ERROR => 'Payment gateway error',
            self::PAYMENT_VERIFICATION_FAILED => 'Payment verification failed',

            self::EMAIL_SEND_FAILED => 'Failed to send email',
            self::REPORT_GENERATION_FAILED => 'Failed to generate report',

            default => 'Unknown error occurred',
        };
    }
}
