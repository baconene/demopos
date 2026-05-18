<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;

class ReportService
{
    public function getDailySalesReport(Carbon $date = null): array
    {
        $date ??= Carbon::today();

        $orders = Order::whereDate('created_at', $date->toDateString())
            ->where('payment_status', 'paid')
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total_amount'),
            'total_discount' => $orders->sum('discount_amount'),
            'total_tax' => $orders->sum('tax_amount'),
            'orders' => $orders,
        ];
    }

    public function getMonthlySalesReport(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->get();

        return [
            'month' => $start->format('Y-m'),
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total_amount'),
            'total_discount' => $orders->sum('discount_amount'),
            'total_tax' => $orders->sum('tax_amount'),
        ];
    }

    public function getProductSalesReport(Carbon $startDate = null, Carbon $endDate = null)
    {
        $startDate ??= Carbon::now()->startOfMonth();
        $endDate ??= Carbon::now()->endOfMonth();

        return \App\Models\OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->selectRaw('order_items.product_id, products.name as product_name, SUM(order_items.quantity) as total_quantity, SUM(order_items.subtotal) as total_sales')
            ->groupBy('order_items.product_id', 'products.name')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function getInventoryValuation()
    {
        return \App\Models\Ingredient::where('is_active', true)
            ->selectRaw('*, (current_quantity) as valuation')
            ->get();
    }
}
