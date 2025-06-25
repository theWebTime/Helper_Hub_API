<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubServiceTypeNameSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('subservice_type_names')->insert([
            [
                'name' => 'House Size (BHK)',
                'slug' => 'bhk',
                'unit_label' => 'BHK',
                'example' => '1 BHK, 2 BHK, 3 BHK, Villa'
            ],
            [
                'name' => 'Time in Hours',
                'slug' => 'hour',
                'unit_label' => 'Hour',
                'example' => '0.5 Hour, 1 Hour, 1.5 Hour, 2 Hours'
            ],
            [
                'name' => 'Person Count',
                'slug' => 'person',
                'unit_label' => 'Person',
                'example' => '1 Person, 2 Persons, 3 Persons'
            ],
            [
                'name' => 'Times per Day',
                'slug' => 'times-per-day',
                'unit_label' => 'Times',
                'example' => '1 time/day, 2 times/day, 3 times/day'
            ],
            [
                'name' => 'Bathroom Count',
                'slug' => 'bathroom-count',
                'unit_label' => 'Bathroom',
                'example' => '1 Bathroom, 2 Bathrooms, 3 Bathrooms'
            ],
            [
                'name' => 'Sofa Seat Count',
                'slug' => 'sofa-seat',
                'unit_label' => 'Seat',
                'example' => '3 Seater, 5 Seater, 7 Seater'
            ],
            [
                'name' => 'Laundry in KG',
                'slug' => 'laundry-kg',
                'unit_label' => 'KG',
                'example' => '5 KG, 10 KG, 15 KG'
            ],
            [
                'name' => 'Flat Type',
                'slug' => 'flat-type',
                'unit_label' => 'Flat',
                'example' => 'Apartment, Penthouse, Studio Flat'
            ],
            [
                'name' => 'Kitchen Type',
                'slug' => 'kitchen-type',
                'unit_label' => 'Kitchen',
                'example' => 'Modular, Traditional, Open Kitchen'
            ],
            [
                'name' => 'Room Type',
                'slug' => 'room-type',
                'unit_label' => 'Room',
                'example' => 'Bedroom, Living Room, Kids Room'
            ],
            [
                'name' => 'Fixed',
                'slug' => 'fixed',
                'unit_label' => null,
                'example' => 'No dropdown needed, fixed pricing'
            ],
        ]);
    }
}
