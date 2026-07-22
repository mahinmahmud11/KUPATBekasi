<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PartnerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_partner_only_displays_its_active_products(): void
    {
        $this->withoutVite();
        Storage::fake('public');

        $logoPath = 'partners/logos/mitra-profil.webp';
        $coverPath = 'partners/covers/mitra-profil.webp';
        Storage::disk('public')->put($logoPath, 'logo');
        Storage::disk('public')->put($coverPath, 'cover');

        $partner = Partner::factory()->create([
            'name' => 'Mitra Profil',
            'owner_name' => 'Pemilik Mitra',
            'short_description' => 'Ringkasan usaha mitra.',
            'description' => 'Deskripsi lengkap usaha mitra.',
            'address' => 'Jalan Usaha Nomor 1',
            'district' => 'Bekasi Selatan',
            'whatsapp' => '6281234567890',
            'instagram_url' => 'https://instagram.com/mitraprofil',
            'logo_path' => $logoPath,
            'cover_path' => $coverPath,
        ]);
        $otherPartner = Partner::factory()->create();
        $category = Category::factory()->create();
        $active = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Mitra Aktif']);
        $inactive = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Mitra Nonaktif', 'is_active' => false]);
        $other = Product::factory()->for($otherPartner)->for($category)->create(['name' => 'Produk Mitra Lain']);

        $counts = [Partner::query()->count(), Product::query()->count()];

        $this->get(route('partners.show', $partner))
            ->assertOk()
            ->assertSee('data-partner-hero', false)
            ->assertSee('data-partner-cover', false)
            ->assertSee('src="'.Storage::disk('public')->url($coverPath).'"', false)
            ->assertSee('alt="Sampul '.$partner->name.'"', false)
            ->assertSee('data-partner-logo', false)
            ->assertSee('src="'.Storage::disk('public')->url($logoPath).'"', false)
            ->assertSee('alt="Logo '.$partner->name.'"', false)
            ->assertSee('<meta property="og:title" content="'.$partner->name.' | '.config('app.name').'">', false)
            ->assertSee('<meta name="twitter:title" content="'.$partner->name.' | '.config('app.name').'">', false)
            ->assertSee('<meta property="og:url" content="'.route('partners.show', $partner).'">', false)
            ->assertSee('<link rel="canonical" href="'.route('partners.show', $partner).'">', false)
            ->assertSee('<meta property="og:type" content="profile">', false)
            ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url($coverPath)).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.url(Storage::disk('public')->url($coverPath)).'">', false)
            ->assertSee($partner->name)
            ->assertSee($partner->short_description)
            ->assertSee($partner->description)
            ->assertSee($partner->owner_name)
            ->assertSee($partner->address)
            ->assertSee($partner->district)
            ->assertSee('href="'.$partner->instagram_url.'"', false)
            ->assertSee('href="https://wa.me/'.$partner->whatsapp.'"', false)
            ->assertSee('1 produk aktif')
            ->assertSee('Produk dari Mitra Ini')
            ->assertSee($active->name)
            ->assertDontSee($inactive->name)
            ->assertDontSee($other->name);

        $this->assertSame($counts, [Partner::query()->count(), Product::query()->count()]);
    }

    public function test_partner_metadata_uses_partner_logo_then_site_logo_fallback(): void
    {
        $this->withoutVite();
        Storage::fake('public');

        $partnerLogoPath = 'partners/logos/social.webp';
        $siteLogoPath = 'site-settings/social.webp';
        Storage::disk('public')->put($partnerLogoPath, 'partner logo');
        Storage::disk('public')->put($siteLogoPath, 'site logo');
        SiteSetting::factory()->create(['logo_path' => $siteLogoPath]);

        $partnerWithLogo = Partner::factory()->create([
            'cover_path' => null,
            'logo_path' => $partnerLogoPath,
        ]);
        $partnerWithoutMedia = Partner::factory()->create([
            'cover_path' => null,
            'logo_path' => null,
        ]);
        $counts = [Partner::query()->count(), SiteSetting::query()->count()];

        $this->get(route('partners.show', $partnerWithLogo))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url($partnerLogoPath)).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.url(Storage::disk('public')->url($partnerLogoPath)).'">', false);

        $this->get(route('partners.show', $partnerWithoutMedia))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url($siteLogoPath)).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.url(Storage::disk('public')->url($siteLogoPath)).'">', false);

        $this->assertSame($counts, [Partner::query()->count(), SiteSetting::query()->count()]);
    }

    public function test_partner_metadata_normalizes_full_description_and_omits_missing_image(): void
    {
        $this->withoutVite();

        $description = '<p>'.str_repeat('Deskripsi mitra panjang   ', 12).'</p>';
        $partner = Partner::factory()->create([
            'short_description' => null,
            'description' => $description,
            'logo_path' => null,
            'cover_path' => null,
        ]);
        $count = Partner::query()->count();
        $expectedDescription = Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags($description))), 160, '');

        $this->get(route('partners.show', $partner))
            ->assertOk()
            ->assertSee('<meta name="description" content="'.$expectedDescription.'">', false)
            ->assertSee('<meta property="og:description" content="'.$expectedDescription.'">', false)
            ->assertSee('<meta name="twitter:description" content="'.$expectedDescription.'">', false)
            ->assertDontSee('<meta property="og:image"', false)
            ->assertDontSee('<meta name="twitter:image"', false);

        $this->assertLessThanOrEqual(160, mb_strlen($expectedDescription));
        $this->assertSame($count, Partner::query()->count());
    }

    public function test_non_public_partner_returns_not_found(): void
    {
        $this->withoutVite();
        $inactive = Partner::factory()->create(['is_active' => false]);
        $deleted = Partner::factory()->create();
        $deleted->delete();

        $this->get(route('partners.show', $inactive))->assertNotFound();
        $this->get('/mitra/'.$deleted->slug)->assertNotFound();
    }

    public function test_partner_profile_has_product_empty_state(): void
    {
        $this->withoutVite();
        $partner = Partner::factory()->create([
            'name' => 'Usaha Tanpa Media',
            'owner_name' => null,
            'description' => null,
            'address' => null,
            'district' => null,
            'instagram_url' => null,
            'whatsapp' => '',
            'logo_path' => null,
            'cover_path' => null,
        ]);

        $count = Partner::query()->count();

        $this->get(route('partners.show', $partner))
            ->assertOk()
            ->assertSee('data-partner-cover-fallback', false)
            ->assertSee('data-partner-logo-fallback', false)
            ->assertSee($partner->short_description)
            ->assertSee('0 produk aktif')
            ->assertSee('Produk aktif dari mitra ini belum tersedia.')
            ->assertDontSee('Nama Pemilik')
            ->assertDontSee('Alamat')
            ->assertDontSee('Kecamatan/Wilayah')
            ->assertDontSee('Buka Instagram')
            ->assertDontSee('Hubungi via WhatsApp');

        $this->assertSame($count, Partner::query()->count());
    }
}
