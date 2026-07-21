<?php

namespace Tests\Feature\Frontend;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_information_routes_are_available_and_empty_fields_are_safe(): void
    {
        $this->withoutVite();

        $this->assertSame('/tentang', route('about', absolute: false));
        $this->assertSame('/kontak', route('contact', absolute: false));
        $this->assertSame('/kebijakan-privasi', route('privacy', absolute: false));
        $this->get(route('about'))->assertOk()->assertSee('Informasi tentang program belum tersedia.');
        $this->get(route('contact'))->assertOk()->assertSee('Informasi kontak belum tersedia.');
        $this->get(route('privacy'))->assertOk()->assertSee('Transaksi dilakukan langsung antara pengunjung dan UMKM.');
        $this->assertSame(0, SiteSetting::query()->count());
    }

    public function test_about_and_contact_display_available_site_settings_without_mutation(): void
    {
        $this->withoutVite();
        $setting = SiteSetting::factory()->create([
            'about_summary' => 'Ringkasan program resmi.',
            'contact_whatsapp' => '6281234567890',
            'contact_email' => 'kontak@example.test',
            'address' => 'Alamat pengujian',
            'instagram_url' => 'https://instagram.com/example',
        ]);

        $this->get(route('about'))->assertOk()->assertSee($setting->about_summary);
        $this->get(route('contact'))->assertOk()->assertSee($setting->contact_whatsapp)
            ->assertSee($setting->contact_email)->assertSee($setting->address)->assertSee($setting->instagram_url);
        $this->assertSame(1, SiteSetting::query()->count());
        $this->assertSame($setting->updated_at->toJSON(), $setting->fresh()->updated_at->toJSON());
    }
}
