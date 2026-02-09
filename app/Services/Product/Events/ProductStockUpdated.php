<?php

namespace App\Services\Product\Events;

use App\Services\Product\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductStockUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The product instance.
     *
     * @var Product
     */
    public $product;

    /**
     * The old stock quantity.
     *
     * @var int
     */
    public $oldStock;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param int $oldStock
     */
    public function __construct(Product $product, int $oldStock)
    {
        $this->product = $product;
        $this->oldStock = $oldStock;
    }
}
