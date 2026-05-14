<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\Product;
use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $burgerProduct = Product::where('sku', 'BURGER001')->first();
        $groundBeef = Ingredient::where('name', 'Ground Beef')->first();
        $buns = Ingredient::where('name', 'Burger Buns')->first();
        $lettuce = Ingredient::where('name', 'Lettuce')->first();
        $tomato = Ingredient::where('name', 'Tomatoes')->first();
        $onion = Ingredient::where('name', 'Onions')->first();

        if ($burgerProduct && $groundBeef && $buns && $lettuce && $tomato && $onion) {
            Recipe::create(['product_id' => $burgerProduct->id, 'ingredient_id' => $groundBeef->id, 'quantity' => 0.25, 'unit' => 'kg']);
            Recipe::create(['product_id' => $burgerProduct->id, 'ingredient_id' => $buns->id, 'quantity' => 1, 'unit' => 'pcs']);
            Recipe::create(['product_id' => $burgerProduct->id, 'ingredient_id' => $lettuce->id, 'quantity' => 0.05, 'unit' => 'kg']);
            Recipe::create(['product_id' => $burgerProduct->id, 'ingredient_id' => $tomato->id, 'quantity' => 0.05, 'unit' => 'kg']);
            Recipe::create(['product_id' => $burgerProduct->id, 'ingredient_id' => $onion->id, 'quantity' => 0.03, 'unit' => 'kg']);
        }
    }
}
