<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class QueueMonitorController extends Controller
{
    public function index(): Response
    {
        $orders = $this->getActiveOrders();

        return Inertia::render('KitchenMonitor', [
            'initialOrders' => $orders,
        ]);
    }

    public static function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'queue_number' => $order->queueNumber?->number,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'table_number' => $order->table_number,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toDateTimeString(),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'special_instructions' => $item->special_instructions,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                ],
            ]),
        ];
    }

    private function getActiveOrders(): array
    {
        return Order::with(['items.product', 'queueNumber'])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($order) => self::formatOrder($order))
            ->toArray();
    }
}
