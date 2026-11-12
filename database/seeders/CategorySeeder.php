<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Gastronomia', 'icon' => 'restaurant'],
            ['name' => 'Turismo', 'icon' => 'terrain'],
            ['name' => 'Cultura & Arte', 'icon' => 'palette'],
            ['name' => 'Vida Noturna', 'icon' => 'local_bar'],
        ];

        foreach ($categories as $index => $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'icon' => $category['icon'],
                'sort_order' => $index,
                'status' => 'active',
            ]);
        }
    }
}