<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $burgers = Category::where('slug', 'burgers')->first();
        $pizzas = Category::where('slug', 'pizzas')->first();
        $desserts = Category::where('slug', 'desserts')->first();
        $beverages = Category::where('slug', 'beverages')->first();
        $appetizers = Category::where('slug', 'appetizers')->first();

        $products = [
            // Burgers
            ['category_id' => $burgers->id, 'name' => 'Classic Burger', 'sku' => 'BURGER001', 'price' => 250.00, 'cost' => 80.00, 'description' => 'Juicy beef patty with lettuce, tomato, onion'],
            ['category_id' => $burgers->id, 'name' => 'Bacon Burger', 'sku' => 'BURGER002', 'price' => 300.00, 'cost' => 100.00, 'description' => 'With crispy bacon and cheese'],
            ['category_id' => $burgers->id, 'name' => 'Double Burger', 'sku' => 'BURGER003', 'price' => 350.00, 'cost' => 120.00, 'description' => 'Two beef patties, double the goodness'],

            // Pizzas
            ['category_id' => $pizzas->id, 'name' => 'Pepperoni Pizza', 'sku' => 'PIZZA001', 'price' => 350.00, 'cost' => 120.00, 'description' => 'Classic pepperoni pizza'],
            ['category_id' => $pizzas->id, 'name' => 'Margherita Pizza', 'sku' => 'PIZZA002', 'price' => 320.00, 'cost' => 100.00, 'description' => 'Tomato, mozzarella, fresh basil'],
            ['category_id' => $pizzas->id, 'name' => 'Hawaiian Pizza', 'sku' => 'PIZZA003', 'price' => 380.00, 'cost' => 130.00, 'description' => 'With pineapple and ham'],

            // Desserts
            ['category_id' => $desserts->id, 'name' => 'Chocolate Cake', 'sku' => 'DESSERT001', 'price' => 180.00, 'cost' => 40.00, 'description' => 'Rich chocolate cake'],
            ['category_id' => $desserts->id, 'name' => 'Cheesecake', 'sku' => 'DESSERT002', 'price' => 200.00, 'cost' => 50.00, 'description' => 'New York style cheesecake'],
            ['category_id' => $desserts->id, 'name' => 'Ice Cream', 'sku' => 'DESSERT003', 'price' => 150.00, 'cost' => 30.00, 'description' => 'Various flavors available'],

            // Beverages
            ['category_id' => $beverages->id, 'name' => 'Soft Drink', 'sku' => 'BEVERAGE001', 'price' => 80.00, 'cost' => 20.00, 'description' => 'Coca-Cola, Sprite, etc.'],
            ['category_id' => $beverages->id, 'name' => 'Iced Tea', 'sku' => 'BEVERAGE002', 'price' => 70.00, 'cost' => 15.00, 'description' => 'Refreshing iced tea'],
            ['category_id' => $beverages->id, 'name' => 'Coffee', 'sku' => 'BEVERAGE003', 'price' => 120.00, 'cost' => 30.00, 'description' => 'Hot brewed coffee'],

            // Appetizers
            ['category_id' => $appetizers->id, 'name' => 'Chicken Wings', 'sku' => 'APP001', 'price' => 220.00, 'cost' => 70.00, 'description' => 'Crispy chicken wings'],
            ['category_id' => $appetizers->id, 'name' => 'Spring Rolls', 'sku' => 'APP002', 'price' => 180.00, 'cost' => 50.00, 'description' => 'Fresh spring rolls'],
            ['category_id' => $appetizers->id, 'name' => 'Nachos', 'sku' => 'APP003', 'price' => 200.00, 'cost' => 60.00, 'description' => 'Cheese nachos with sour cream'],
        ];

        foreach ($products as $index => $product) {
            $product['display_order'] = $index;
            Product::create($product);
        }
    }
}
