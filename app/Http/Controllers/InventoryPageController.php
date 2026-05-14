<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use Inertia\Inertia;
use Inertia\Response;

class InventoryPageController extends Controller
{
    public function index(): Response
    {
        $ingredients = Ingredient::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'unit' => $i->unit,
                'current_quantity' => (float) $i->current_quantity,
                'min_quantity' => (float) $i->min_quantity,
                'cost_per_unit' => (float) $i->cost_per_unit,
                'is_low_stock' => $i->current_quantity <= $i->min_quantity,
            ]);

        $recentTransactions = InventoryTransaction::with(['ingredient', 'user'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'ingredient_name' => $t->ingredient?->name,
                'type' => $t->type,
                'quantity' => (float) $t->quantity,
                'old_quantity' => (float) $t->old_quantity,
                'new_quantity' => (float) $t->new_quantity,
                'user_name' => $t->user?->name,
                'notes' => $t->notes,
                'created_at' => $t->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('InventoryManagement', [
            'ingredients' => $ingredients,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
