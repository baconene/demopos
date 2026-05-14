<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            ['name' => 'Ground Beef', 'unit' => 'kg', 'current_quantity' => 50, 'min_quantity' => 10],
            ['name' => 'Burger Buns', 'unit' => 'pcs', 'current_quantity' => 200, 'min_quantity' => 50],
            ['name' => 'Cheese Slices', 'unit' => 'pcs', 'current_quantity' => 300, 'min_quantity' => 100],
            ['name' => 'Bacon', 'unit' => 'kg', 'current_quantity' => 20, 'min_quantity' => 5],
            ['name' => 'Lettuce', 'unit' => 'kg', 'current_quantity' => 30, 'min_quantity' => 5],
            ['name' => 'Tomatoes', 'unit' => 'kg', 'current_quantity' => 25, 'min_quantity' => 5],
            ['name' => 'Onions', 'unit' => 'kg', 'current_quantity' => 40, 'min_quantity' => 10],
            ['name' => 'Pizza Dough', 'unit' => 'kg', 'current_quantity' => 60, 'min_quantity' => 15],
            ['name' => 'Mozzarella', 'unit' => 'kg', 'current_quantity' => 35, 'min_quantity' => 10],
            ['name' => 'Pepperoni', 'unit' => 'kg', 'current_quantity' => 20, 'min_quantity' => 5],
            ['name' => 'Tomato Sauce', 'unit' => 'ltr', 'current_quantity' => 50, 'min_quantity' => 10],
            ['name' => 'Pineapple', 'unit' => 'kg', 'current_quantity' => 15, 'min_quantity' => 5],
            ['name' => 'Ham', 'unit' => 'kg', 'current_quantity' => 10, 'min_quantity' => 3],
            ['name' => 'Chicken Breast', 'unit' => 'kg', 'current_quantity' => 30, 'min_quantity' => 8],
            ['name' => 'Chocolate', 'unit' => 'kg', 'current_quantity' => 15, 'min_quantity' => 3],
            ['name' => 'Flour', 'unit' => 'kg', 'current_quantity' => 100, 'min_quantity' => 20],
            ['name' => 'Sugar', 'unit' => 'kg', 'current_quantity' => 50, 'min_quantity' => 10],
            ['name' => 'Butter', 'unit' => 'kg', 'current_quantity' => 25, 'min_quantity' => 5],
            ['name' => 'Eggs', 'unit' => 'dozen', 'current_quantity' => 30, 'min_quantity' => 10],
            ['name' => 'Milk', 'unit' => 'ltr', 'current_quantity' => 40, 'min_quantity' => 10],
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::create($ingredient);
        }
    }
}
