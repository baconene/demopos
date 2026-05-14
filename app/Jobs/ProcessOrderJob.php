<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOrderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private Order $order) {}

    public function handle(InventoryService $inventoryService): void
    {
        foreach ($this->order->items as $item) {
            if (!$inventoryService->deductForOrder($item)) {
                // Insufficient inventory - cancel order
                $this->order->update(['status' => 'cancelled']);
                event(new \App\Events\OrderStatusChanged($this->order));

                return;
            }
        }

        $this->order->update(['status' => 'preparing']);
        event(new \App\Events\OrderStatusChanged($this->order));
    }
}
