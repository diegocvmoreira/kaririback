<?php

namespace Database\Seeders;

use App\Models\Place;
use App\Models\City;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlaceSeeder extends Seeder
{
    public function run(): void
    {
        // Puxa os IDs reais que acabaram de ser criados nos Seeders anteriores
        $crato = City::where('slug', 'crato')->first();
        $juazeiro = City::where('slug', 'juazeiro-do-norte')->first();
        
        $food = Category::where('slug', 'gastronomia')->first();
        $culture = Category::where('slug', 'cultura-arte')->first();

        $places = [
            [
                'city_id' => $crato->id,
                'category_id' => $culture->id,
                'name' => 'Espaço de Cultura Seu Ciço',
                'short_description' => 'Centro de convivência e resistência cultural no Crato.',
                'featured' => true,
            ],
            [
                'city_id' => $juazeiro->id,
                'category_id' => $food->id,
                'name' => 'Café Cariri',
                'short_description' => 'Café regional com tapiocas e cuscuz recheado.',
                'featured' => false,
            ],
            [
                'city_id' => $crato->id,
                'category_id' => $culture->id,
                'name' => 'Ponto de Cultura Tardinha',
                'short_description' => 'Local de realização do Festival Tardinha.',
                'featured' => true,
            ],
        ];

        foreach ($places as $place) {
            Place::create([
                'city_id' => $place['city_id'],
                'category_id' => $place['category_id'],
                'name' => $place['name'],
                'slug' => Str::slug($place['name']),
                'short_description' => $place['short_description'],
                'status' => 'active',
                'featured' => $place['featured'],
                'price_level' => rand(1, 3),
                'rating' => rand(40, 50) / 10, // Gera notas de 4.0 a 5.0
                'reviews_count' => rand(10, 150),
            ]);
        }
    }
}