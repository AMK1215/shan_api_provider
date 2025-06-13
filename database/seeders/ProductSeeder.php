<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::create([
            'code' => 1002,
            'name' => 'Product 1002',
            'active' => true,
        ]);

        Product::create([
            'code' => 1020,
            'name' => 'Product 1020',
            'active' => true,
        ]);

        Product::create([
            'code' => 1009,
            'name' => 'Product 1009',
            'active' => true,
        ]);
    }
}
