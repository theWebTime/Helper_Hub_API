<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('services')->insert([[
            'name' => 'One Time Service',
            'description' => 'You can get a person for one day only.',
            'image' => null,
            'status' => 1,
        ],
        [
            'name' => 'Monthly Service',
            'description' => 'You can get a person for one month.',
            'image' => null,
            'status' => 1,
        ]]);
    }
}
