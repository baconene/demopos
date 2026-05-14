<?php

namespace Database\Seeders;

use App\Models\Modifier;
use Illuminate\Database\Seeder;

class ModifierSeeder extends Seeder
{
    public function run(): void
    {
        $modifiers = [
            ['name' => 'Extra Cheese', 'price' => 25.00],
            ['name' => 'Bacon', 'price' => 50.00],
            ['name' => 'Extra Sauce', 'price' => 15.00],
            ['name' => 'Grilled Onions', 'price' => 20.00],
            ['name' => 'Jalapeños', 'price' => 15.00],
            ['name' => 'Sour Cream', 'price' => 20.00],
            ['name' => 'Caramel Drizzle', 'price' => 30.00],
            ['name' => 'Whipped Cream', 'price' => 25.00],
        ];

        foreach ($modifiers as $modifier) {
            Modifier::create($modifier);
        }
    }
}
