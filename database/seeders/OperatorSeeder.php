<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Operator;

class OperatorSeeder extends Seeder
{
    public function run()
    {
        Operator::create([
            'code' => 'a3h1',
            'secret_key' => 'shana3h1', // change to a secure value
            'active' => true,
        ]);

        // You can add more operators here
        Operator::create([
            'code' => 'XYZ1',
            'secret_key' => 'anothersecret',
            'active' => true,
        ]);
    }
}
