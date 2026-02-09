<?php

namespace App\Services\Order\Listeners;

use App\Services\Order\Events\OrderPlaced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateProductStock implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param OrderPlaced $event
     * @return void
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        Log::info('Order placed event handled', [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'total_amount' => $order->total_amount,
            'items_count' => $order->items->count(),
        ]);

        // Additional logic can be added here
        // Stock update is already handled in the controller for this implementation
        // But this listener demonstrates event-driven architecture
    }
}
