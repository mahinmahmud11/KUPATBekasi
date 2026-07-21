<?php

namespace Tests\Feature\Frontend;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_route_renders_the_public_home_shell(): void
    {
        $this->withoutVite();

        $this->assertSame('/', route('home', absolute: false));

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertViewIs('home')
            ->assertSee('<h1>KUPATBekasi</h1>', false)
            ->assertSee('<header>', false)
            ->assertSee('<main>', false)
            ->assertSee('<footer>', false)
            ->assertSee('<title>Beranda', false);
    }

    public function test_public_layout_uses_application_name_when_site_settings_are_empty(): void
    {
        $this->withoutVite();
        config(['app.name' => 'Nama Aplikasi']);

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Nama Aplikasi')
            ->assertSee('<title>Beranda | Nama Aplikasi</title>', false)
            ->assertDontSee('data-site-logo', false)
            ->assertDontSee('<link rel="icon"', false);
    }

    public function test_public_layout_uses_site_setting_identity_and_media(): void
    {
        $this->withoutVite();
        Storage::fake('public');

        $logoPath = 'site-settings/logo.png';
        $faviconPath = 'site-settings/favicon.png';

        Storage::disk('public')->put($logoPath, 'logo');
        Storage::disk('public')->put($faviconPath, 'favicon');

        SiteSetting::factory()->create([
            'site_name' => 'Pasar Bekasi',
            'tagline' => 'Produk lokal pilihan warga.',
            'logo_path' => $logoPath,
            'favicon_path' => $faviconPath,
        ]);

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Pasar Bekasi')
            ->assertSee('Produk lokal pilihan warga.')
            ->assertSee('<title>Beranda | Pasar Bekasi</title>', false)
            ->assertSee('<img data-site-logo src="'.Storage::disk('public')->url($logoPath).'" alt="Pasar Bekasi">', false)
            ->assertSee('src="'.Storage::disk('public')->url($logoPath).'"', false)
            ->assertSee('alt="Pasar Bekasi"', false)
            ->assertSee('href="'.Storage::disk('public')->url($faviconPath).'"', false);

        $this->assertSame(1, SiteSetting::query()->count());
    }
}
