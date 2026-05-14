<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\OrderItem;
use App\Enums\InventoryTransactionType;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    public function deductForOrder(OrderItem $orderItem): bool
    {
        $product = $orderItem->product;
        $recipes = $product->recipes;

        foreach ($recipes as $recipe) {
            $ingredient = $recipe->ingredient;
            $requiredQuantity = $recipe->quantity * $orderItem->quantity;

            if ($ingredient->current_quantity < $requiredQuantity) {
                return false;
            }

            $this->recordTransaction(
                $ingredient,
                $requiredQuantity,
                InventoryTransactionType::STOCK_OUT,
                'order_' . $orderItem->order_id
            );
        }

        return true;
    }

    public function recordTransaction(
        Ingredient $ingredient,
        float $quantity,
        InventoryTransactionType $type,
        ?string $reference = null,
        ?string $notes = null,
    ): InventoryTransaction {
        $oldQuantity = $ingredient->current_quantity;

        match ($type) {
            InventoryTransactionType::STOCK_IN => $ingredient->increment('current_quantity', $quantity),
            InventoryTransactionType::STOCK_OUT => $ingredient->decrement('current_quantity', $quantity),
            InventoryTransactionType::ADJUSTMENT => $ingredient->update(['current_quantity' => $quantity]),
            InventoryTransactionType::WASTE => $ingredient->decrement('current_quantity', $quantity),
        };

        return InventoryTransaction::create([
            'ingredient_id' => $ingredient->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'quantity' => $quantity,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $ingredient->fresh()->current_quantity,
            'reference' => $reference,
            'notes' => $notes,
        ]);
    }

    public function checkAvailability(OrderItem $orderItem): bool
    {
        $product = $orderItem->product;
        $recipes = $product->recipes;

        foreach ($recipes as $recipe) {
            $ingredient = $recipe->ingredient;
            $requiredQuantity = $recipe->quantity * $orderItem->quantity;

            if ($ingredient->current_quantity < $requiredQuantity) {
                return false;
            }
        }

        return true;
    }

    public function getLowStockItems()
    {
        return Ingredient::whereColumn('current_quantity', '<=', 'min_quantity')
            ->where('is_active', true)
            ->get();
    }
}
