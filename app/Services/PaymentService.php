<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function processPayment(Order $order, array $paymentData): Payment
    {
        return DB::transaction(function () use ($order, $paymentData) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'amount' => $paymentData['amount'],
                'method' => $paymentData['method'],
                'status' => PaymentStatus::COMPLETED->value,
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            // Update order payment status
            $totalPaid = Payment::where('order_id', $order->id)
                ->where('status', PaymentStatus::COMPLETED->value)
                ->sum('amount');

            if ($totalPaid >= $order->total_amount) {
                $order->update(['payment_status' => PaymentStatus::COMPLETED->value]);
            }

            return $payment;
        });
    }

    public function refundPayment(Payment $payment, array $refundData): \App\Models\Refund
    {
        return DB::transaction(function () use ($payment, $refundData) {
            $refund = \App\Models\Refund::create([
                'payment_id' => $payment->id,
                'user_id' => auth()->id(),
                'amount' => $refundData['amount'],
                'status' => PaymentStatus::COMPLETED->value,
                'reason' => $refundData['reason'] ?? null,
                'reference' => $refundData['reference'] ?? null,
            ]);

            $payment->update(['status' => PaymentStatus::REFUNDED->value]);
            $payment->order->update(['payment_status' => PaymentStatus::REFUNDED->value]);

            return $refund;
        });
    }
}
