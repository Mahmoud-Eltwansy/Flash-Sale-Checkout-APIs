<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => 'Iphone 17',
            'price' => 1100,
            'total_stock' => 100,
        ]);
        Product::create([
            'name' => 'Lenovo Laptop',
            'price' => 1400,
            'total_stock' => 5,
        ]);
    }
}
