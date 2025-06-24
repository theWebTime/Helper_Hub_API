<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubServiceTypeNameSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('subservice_type_names')->insert([
            ['name' => 'BHK', 'slug' => 'bhk', 'unit_label' => 'BHK'],
            ['name' => 'Hour', 'slug' => 'hour', 'unit_label' => 'Hour'],
            ['name' => 'Person Count', 'slug' => 'person', 'unit_label' => 'Person'],
            ['name' => 'Times per Day', 'slug' => 'times-per-day', 'unit_label' => 'Times'],
            ['name' => 'Bathroom Count', 'slug' => 'bathroom-count', 'unit_label' => 'Bathroom'],
            ['name' => 'Sofa Seat Count', 'slug' => 'sofa-seat', 'unit_label' => 'Seat'],
            ['name' => 'Laundry KG', 'slug' => 'laundry-kg', 'unit_label' => 'KG'],
            ['name' => 'Flat Type', 'slug' => 'flat-type', 'unit_label' => 'Flat'],
            ['name' => 'Kitchen Type', 'slug' => 'kitchen-type', 'unit_label' => 'Kitchen'],
            ['name' => 'Room Type', 'slug' => 'room-type', 'unit_label' => 'Room'],
            ['name' => 'Fixed', 'slug' => 'fixed', 'unit_label' => null],
        ]);
    }
}
