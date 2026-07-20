<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SiteSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_name' => 'KUPATBekasi',
            'tagline' => fake()->sentence(5),
            'about_summary' => fake()->paragraph(),
            'contact_whatsapp' => '628000'.fake()->unique()->numerify('#######'),
            'contact_email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'instagram_url' => null,
            'logo_path' => null,
            'favicon_path' => null,
        ];
    }
}
