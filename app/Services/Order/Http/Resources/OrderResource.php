<?php

namespace App\Services\Order\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\OrderStatus;

/**
 * Order API Resource
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = OrderStatus::from($this->status);

        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'order_number' => $this->order_number ?? 'ORD-' . str_pad($this->id, 6, '0', STR_PAD_LEFT),
            'total' => [
                'amount' => (float) $this->total_amount,
                'formatted' => '$' . number_format($this->total_amount, 2),
                'currency' => 'USD',
            ],
            'status' => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            'items_count' => $this->items->count(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => $this->whenLoaded('payment', function () {
                return new PaymentResource($this->payment);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
