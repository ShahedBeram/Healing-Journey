<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'المحتوى التوعوي', 'slug' => 'awareness', 'is_active' => true],
            ['name' => 'المحتوى التحفيزي', 'slug' => 'motivational', 'is_active' => true],
            ['name' => 'أعمال الأطفال', 'slug' => 'child-content', 'is_active' => true],
            ['name' => 'الجلسات', 'slug' => 'sessions', 'is_active' => true],
            ['name' => 'الأنشطة', 'slug' => 'activities', 'is_active' => true],
            ['name' => 'حملات التبرع', 'slug' => 'donations', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
