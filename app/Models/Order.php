<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'queue_number_id',
        'order_type',
        'status',
        'table_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'payment_status',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function queueNumber()
    {
        return $this->belongsTo(QueueNumber::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function calculateTotals()
    {
        $subtotal = $this->items()->sum('subtotal');
        $taxAmount = $subtotal * 0.12; // 12% VAT
        $totalAmount = $subtotal + $taxAmount - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        return $this;
    }
}
