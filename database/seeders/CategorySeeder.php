<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Burgers', 'slug' => 'burgers', 'description' => 'Fresh and delicious burgers'],
            ['name' => 'Pizzas', 'slug' => 'pizzas', 'description' => 'Authentic pizzas'],
            ['name' => 'Desserts', 'slug' => 'desserts', 'description' => 'Sweet desserts'],
            ['name' => 'Beverages', 'slug' => 'beverages', 'description' => 'Drinks and beverages'],
            ['name' => 'Appetizers', 'slug' => 'appetizers', 'description' => 'Starters and appetizers'],
        ];

        foreach ($categories as $index => $category) {
            $category['display_order'] = $index;
            Category::create($category);
        }
    }
}
