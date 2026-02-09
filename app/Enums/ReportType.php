<?php

namespace App\Enums;

/**
 * Report Type Enum
 */
enum ReportType: string
{
    case SALES = 'sales';
    case ORDERS = 'orders';
    case PAYMENTS = 'payments';
    case CUSTOMERS = 'customers';
    case PRODUCTS = 'products';
    case INVENTORY = 'inventory';

    /**
     * Get all report types
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get report type label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::SALES => 'Sales Report',
            self::ORDERS => 'Orders Report',
            self::PAYMENTS => 'Payments Report',
            self::CUSTOMERS => 'Customers Report',
            self::PRODUCTS => 'Products Report',
            self::INVENTORY => 'Inventory Report',
        };
    }

    /**
     * Get report file name
     *
     * @return string
     */
    public function fileName(): string
    {
        return $this->value . '_report_' . now()->format('Y-m-d_His') . '.pdf';
    }
}
