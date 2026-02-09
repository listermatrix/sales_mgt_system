<?php

namespace App\Enums;

/**
 * Order Status Enum
 *
 * Using PHP 8.1+ Enums for type-safe status handling
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    /**
     * Get all status values
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status label for display
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    /**
     * Get status color for UI
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::FAILED => 'red',
            self::REFUNDED => 'orange',
        };
    }

    /**
     * Check if order can be cancelled
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING]);
    }

    /**
     * Check if order can be refunded
     *
     * @return bool
     */
    public function canBeRefunded(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if order is in a final state
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::FAILED, self::REFUNDED]);
    }

    /**
     * Get next possible statuses
     *
     * @return array
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::COMPLETED, self::FAILED, self::CANCELLED],
            self::COMPLETED => [self::REFUNDED],
            self::CANCELLED, self::FAILED, self::REFUNDED => [],
        };
    }
}
