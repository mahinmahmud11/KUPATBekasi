<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'image_path' => 'products/placeholders/'.fake()->unique()->uuid().'.webp',
            'alt_text' => fake()->sentence(4),
            'sort_order' => 0,
        ];
    }
}
