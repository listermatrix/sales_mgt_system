<?php

namespace App\Services\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product API Resource
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => [
                'amount' => (float) $this->price,
                'formatted' => '$' . number_format($this->price, 2),
                'currency' => 'USD',
            ],
            'stock' => [
                'quantity' => $this->stock_quantity,
                'available' => $this->stock_quantity > 0,
                'status' => $this->getStockStatus(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get stock status
     *
     * @return string
     */
    private function getStockStatus(): string
    {
        if ($this->stock_quantity === 0) {
            return 'out_of_stock';
        }

        if ($this->stock_quantity <= 10) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
