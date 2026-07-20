<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'partner_id' => Partner::factory(),
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->numberBetween(10_000, 1_000_000),
            'unit' => fake()->randomElement(['pcs', 'pak', 'box', 'botol']),
            'main_image_path' => null,
            'stock_status' => 'available',
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
