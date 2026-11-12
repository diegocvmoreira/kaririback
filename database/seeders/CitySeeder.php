<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Crato', 'latitude' => -7.2341, 'longitude' => -39.4124],
            ['name' => 'Juazeiro do Norte', 'latitude' => -7.2016, 'longitude' => -39.3146],
            ['name' => 'Barbalha', 'latitude' => -7.3113, 'longitude' => -39.3033],
        ];

        foreach ($cities as $city) {
            City::create([
                'name' => $city['name'],
                'slug' => Str::slug($city['name']),
                'state' => 'CE',
                'latitude' => $city['latitude'],
                'longitude' => $city['longitude'],
                'status' => 'active',
            ]);
        }
    }
}