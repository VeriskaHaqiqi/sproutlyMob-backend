<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Indoor Plants'],
            ['name' => 'Outdoor Plants'],
            ['name' => 'Pest Control'],
            ['name' => 'Hydroponics'],
            ['name' => 'Soil Science'],
            ['name' => 'Crop Management'],
            ['name' => 'Technology'],
        ];

        DB::table('article_categories')->insert($categories);
    }
}