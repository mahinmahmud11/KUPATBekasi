<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'owner_name' => fake()->name(),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'district' => fake()->randomElement([
                'Bekasi Barat',
                'Bekasi Selatan',
                'Bekasi Timur',
                'Bekasi Utara',
            ]),
            'whatsapp' => '628000'.fake()->unique()->numerify('#######'),
            'instagram_url' => null,
            'logo_path' => null,
            'cover_path' => null,
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
