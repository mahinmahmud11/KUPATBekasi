<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BannerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'subtitle' => fake()->sentence(),
            'image_path' => null,
            'button_label' => null,
            'button_url' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
