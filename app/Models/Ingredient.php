<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'current_quantity',
        'min_quantity',
        'track_inventory',
        'is_active',
    ];

    protected $casts = [
        'current_quantity' => 'decimal:3',
        'min_quantity' => 'decimal:3',
        'track_inventory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_quantity <= $this->min_quantity;
    }
}
