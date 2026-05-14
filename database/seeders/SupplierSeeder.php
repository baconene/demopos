<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Metro Food Supplier',
                'contact_person' => 'Mr. Santos',
                'phone' => '555-0101',
                'email' => 'metro@supplier.local',
                'address' => '123 Food St, Manila',
            ],
            [
                'name' => 'Prime Meat Co.',
                'contact_person' => 'Ms. Rodriguez',
                'phone' => '555-0102',
                'email' => 'prime@meat.local',
                'address' => '456 Meat Ave, Quezon City',
            ],
            [
                'name' => 'Fresh Produce Inc.',
                'contact_person' => 'Mr. Garcia',
                'phone' => '555-0103',
                'email' => 'fresh@produce.local',
                'address' => '789 Vegetable Ln, Pasig',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
