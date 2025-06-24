<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::create([
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
        ]);
    }
}
