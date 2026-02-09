<?php

namespace App\Services\Order\Http\Resources;
use App\Services\Product\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Order Item API Resource
 */
class OrderItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => $this->quantity,
            'unit_price' => [
                'amount' => (float)$this->unit_price,
                'formatted' => '$' . number_format($this->unit_price, 2),
            ],
            'subtotal' => [
                'amount' => (float)$this->subtotal,
                'formatted' => '$' . number_format($this->subtotal, 2),
            ],
        ];
    }
}
