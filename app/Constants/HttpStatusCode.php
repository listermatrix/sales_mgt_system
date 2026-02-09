<?php

namespace App\Constants;

/**
 * HTTP Status Codes
 *
 * Centralized HTTP status codes for consistent API responses
 */
class HttpStatusCode
{
    // Success Codes (2xx)
    public const OK = 200;
    public const CREATED = 201;
    public const ACCEPTED = 202;
    public const NO_CONTENT = 204;

    // Client Error Codes (4xx)
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const CONFLICT = 409;
    public const UNPROCESSABLE_ENTITY = 422;
    public const TOO_MANY_REQUESTS = 429;

    // Server Error Codes (5xx)
    public const INTERNAL_SERVER_ERROR = 500;
    public const SERVICE_UNAVAILABLE = 503;
    public const GATEWAY_TIMEOUT = 504;

    /**
     * Get status text for a given code
     *
     * @param int $code
     * @return string
     */
    public static function getText(int $code): string
    {
        return match ($code) {
            self::OK => 'OK',
            self::CREATED => 'Created',
            self::ACCEPTED => 'Accepted',
            self::NO_CONTENT => 'No Content',
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::CONFLICT => 'Conflict',
            self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            self::TOO_MANY_REQUESTS => 'Too Many Requests',
            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::SERVICE_UNAVAILABLE => 'Service Unavailable',
            self::GATEWAY_TIMEOUT => 'Gateway Timeout',
            default => 'Unknown Status',
        };
    }

    /**
     * Check if status code is successful (2xx)
     *
     * @param int $code
     * @return bool
     */
    public static function isSuccessful(int $code): bool
    {
        return $code >= 200 && $code < 300;
    }

    /**
     * Check if status code is client error (4xx)
     *
     * @param int $code
     * @return bool
     */
    public static function isClientError(int $code): bool
    {
        return $code >= 400 && $code < 500;
    }

    /**
     * Check if status code is server error (5xx)
     *
     * @param int $code
     * @return bool
     */
    public static function isServerError(int $code): bool
    {
        return $code >= 500 && $code < 600;
    }
}
