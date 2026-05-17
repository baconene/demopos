<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Inertia\Inertia;

class ProductPageController extends Controller
{
    public function index(): \Inertia\Response
    {
        $products = Product::with(['category', 'recipes.ingredient'])
            ->orderBy('category_id')
            ->orderBy('display_order')
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'sku'           => $p->sku,
                'description'   => $p->description,
                'price'         => (float) $p->price,
                'cost'          => (float) $p->cost,
                'is_active'     => $p->is_active,
                'display_order' => $p->display_order,
                'category_id'   => $p->category_id,
                'category_name' => $p->category?->name,
                'recipes'       => $p->recipes->map(fn ($r) => [
                    'ingredient_id'   => $r->ingredient_id,
                    'ingredient_name' => $r->ingredient?->name,
                    'quantity'        => (float) $r->quantity,
                    'unit'            => $r->unit,
                ])->values(),
            ]);

        $categories  = Category::where('is_active', true)->orderBy('display_order')->get(['id', 'name']);
        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit']);

        return Inertia::render('ProductManagement', compact('products', 'categories', 'ingredients'));
    }
}
