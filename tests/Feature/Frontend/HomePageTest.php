<?php

namespace Tests\Feature\Frontend;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            ->assertDontSee('<link rel="icon"', false)
            ->assertDontSee('<meta property="og:image"', false)
            ->assertDontSee('<meta name="twitter:image"', false);
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
            ->assertSee('data-site-logo', false)
            ->assertSee('src="'.Storage::disk('public')->url($logoPath).'"', false)
            ->assertSee('alt="Pasar Bekasi"', false)
            ->assertSee('href="'.Storage::disk('public')->url($faviconPath).'"', false)
            ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url($logoPath)).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.url(Storage::disk('public')->url($logoPath)).'">', false);

        $this->assertSame(1, SiteSetting::query()->count());
    }

    public function test_home_renders_all_active_banners_in_order_with_slider_controls(): void
    {
        $this->withoutVite();
        Storage::fake('public');

        $imagePath = 'banners/hero.webp';
        Storage::disk('public')->put($imagePath, 'hero image');

        $laterBanner = Banner::factory()->create([
            'title' => 'Banner Aktif Berikutnya',
            'subtitle' => 'Konten banner kedua.',
            'is_active' => true,
            'sort_order' => 20,
        ]);
        $inactiveBanner = Banner::factory()->create([
            'title' => 'Banner Tidak Aktif',
            'is_active' => false,
            'sort_order' => 0,
        ]);
        $heroBanner = Banner::factory()->create([
            'title' => 'Belanja Produk Lokal',
            'subtitle' => 'Temukan pilihan produk UMKM Bekasi.',
            'image_path' => $imagePath,
            'button_label' => 'Lihat Katalog',
            'button_url' => 'https://example.test/produk',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $bannerCount = Banner::query()->count();
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('data-home-slider', false)
            ->assertSeeInOrder([$heroBanner->title, $laterBanner->title])
            ->assertSee($heroBanner->title)
            ->assertSee($heroBanner->subtitle)
            ->assertSee('src="'.Storage::disk('public')->url($imagePath).'"', false)
            ->assertSee('alt="'.$heroBanner->title.'"', false)
            ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url($imagePath)).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.url(Storage::disk('public')->url($imagePath)).'">', false)
            ->assertSee($heroBanner->button_label)
            ->assertSee('href="'.$heroBanner->button_url.'"', false)
            ->assertSee($laterBanner->title)
            ->assertSee('class="overflow-hidden" data-home-slider-viewport', false)
            ->assertSee('class="relative grid" data-home-slider-track', false)
            ->assertSee('data-home-slide data-home-slide-transition data-slide-index="0" aria-hidden="false"', false)
            ->assertSee('invisible pointer-events-none', false)
            ->assertSee('data-home-slide data-home-slide-transition data-slide-index="1" aria-hidden="true"', false)
            ->assertSee('data-home-slider-indicator', false)
            ->assertSee('data-home-slider-dot', false)
            ->assertSee('aria-current="true"', false)
            ->assertSee('aria-current="false"', false)
            ->assertDontSee('data-home-slider-previous', false)
            ->assertDontSee('data-home-slider-next', false)
            ->assertDontSee('>Sebelumnya<', false)
            ->assertDontSee('>Berikutnya<', false)
            ->assertDontSee($inactiveBanner->title);

        $this->assertSame($bannerCount, Banner::query()->count());
    }

    public function test_hero_button_supports_an_internal_url(): void
    {
        $this->withoutVite();

        Banner::factory()->create([
            'button_label' => 'Jelajahi Produk',
            'button_url' => '/produk',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="/produk"', false)
            ->assertSee('Jelajahi Produk');
    }

    public function test_single_banner_does_not_render_slider_controls(): void
    {
        $this->withoutVite();

        $banner = Banner::factory()->create(['title' => 'Banner Tunggal']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($banner->title)
            ->assertSee('data-home-slider', false)
            ->assertSee('data-home-slider-viewport', false)
            ->assertSee('data-home-slider-track', false)
            ->assertSee('data-home-slide-transition', false)
            ->assertSee('aria-hidden="false"', false)
            ->assertDontSee('data-home-slider-previous', false)
            ->assertDontSee('data-home-slider-next', false)
            ->assertDontSee('data-home-slider-indicator', false);
    }

    public function test_home_displays_the_shell_fallback_when_no_active_banner_exists(): void
    {
        $this->withoutVite();

        $inactiveBanner = Banner::factory()->create([
            'title' => 'Banner Tersembunyi',
            'is_active' => false,
        ]);

        $bannerCount = Banner::query()->count();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<h1>KUPATBekasi</h1>', false)
            ->assertSee('Halaman publik KUPATBekasi sedang disiapkan.')
            ->assertDontSee($inactiveBanner->title);

        $this->assertSame($bannerCount, Banner::query()->count());
    }

    public function test_home_sections_only_display_active_featured_content(): void
    {
        $this->withoutVite();

        $category = Category::factory()->create(['name' => 'Kategori Aktif']);
        $inactiveCategory = Category::factory()->create(['name' => 'Kategori Nonaktif', 'is_active' => false]);
        $partner = Partner::factory()->create(['name' => 'Mitra Unggulan Aktif', 'is_featured' => true]);
        $inactivePartner = Partner::factory()->create(['name' => 'Mitra Nonaktif', 'is_featured' => true, 'is_active' => false]);
        Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Unggulan Aktif', 'is_featured' => true]);
        Product::factory()->for($inactivePartner)->for($category)->create(['name' => 'Produk Mitra Nonaktif', 'is_featured' => true]);
        Product::factory()->for($partner)->for($inactiveCategory)->create(['name' => 'Produk Kategori Nonaktif', 'is_featured' => true]);

        $counts = [Category::query()->count(), Partner::query()->count(), Product::query()->count()];

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kategori Aktif')
            ->assertSee('Mitra Unggulan Aktif')
            ->assertSee('Produk Unggulan Aktif')
            ->assertDontSee('Kategori Nonaktif')
            ->assertDontSee('Mitra Nonaktif')
            ->assertDontSee('Produk Mitra Nonaktif')
            ->assertDontSee('Produk Kategori Nonaktif');

        $this->assertSame($counts, [Category::query()->count(), Partner::query()->count(), Product::query()->count()]);
    }

    public function test_layout_navigation_uses_public_named_routes_and_home_meta_description(): void
    {
        $this->withoutVite();

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertSee('href="'.route('partners.index').'"', false)
            ->assertSee('href="'.route('about').'"', false)
            ->assertSee('href="'.route('contact').'"', false)
            ->assertSee('href="'.route('privacy').'"', false)
            ->assertSee('<meta property="og:title" content="Beranda | '.config('app.name').'">', false)
            ->assertSee('<meta name="twitter:title" content="Beranda | '.config('app.name').'">', false)
            ->assertSee('<meta property="og:url" content="'.route('home').'">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('<meta name="twitter:card" content="summary">', false)
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('<meta name="description" content="Temukan produk dan profil UMKM binaan Kota Bekasi melalui katalog digital KUPATBekasi.">', false)
            ->assertSee('<meta property="og:description" content="Temukan produk dan profil UMKM binaan Kota Bekasi melalui katalog digital KUPATBekasi.">', false)
            ->assertSee('<meta name="twitter:description" content="Temukan produk dan profil UMKM binaan Kota Bekasi melalui katalog digital KUPATBekasi.">', false);
    }

    public function test_public_layout_includes_government_endorsement_identity(): void
    {
        $this->withoutVite();
        SiteSetting::factory()->create(['logo_path' => 'fake-logo.png']);

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Didukung oleh')
            ->assertSee('Dinas Koperasi Usaha Kecil dan Menengah')
            ->assertSee('Pemerintah Kota Bekasi')
            ->assertSee('img/logo-kota-bekasi.png')
            ->assertSee('alt="Logo Kota Bekasi"', false)
            ->assertSee('data-government-agency', false)
            ->assertSee('data-government-brand', false)
            ->assertSee('data-government-logo', false)
            ->assertSee('data-site-logo', false);
    }

    public function test_home_metadata_falls_back_to_site_setting_logo_when_first_banner_has_no_image(): void
    {
        $this->withoutVite();
        Storage::fake('public');

        $logoPath = 'site-settings/fallback-logo.png';
        Storage::disk('public')->put($logoPath, 'logo content');
        SiteSetting::factory()->create(['logo_path' => $logoPath]);

        Banner::factory()->create([
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url($logoPath)).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.url(Storage::disk('public')->url($logoPath)).'">', false);
    }

    public function test_home_metadata_description_uses_about_summary_when_available_and_normalizes_text(): void
    {
        $this->withoutVite();

        $rawAbout = '<p>Program   <strong>pembinaan</strong> UMKM Kota Bekasi '.str_repeat('merupakan wadah etalase digital resmi. ', 6).'</p>';
        SiteSetting::factory()->create([
            'about_summary' => $rawAbout,
        ]);

        $expectedDescription = Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags($rawAbout))), 160, '');

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('<meta name="description" content="'.$expectedDescription.'">', false)
            ->assertSee('<meta property="og:description" content="'.$expectedDescription.'">', false)
            ->assertSee('<meta name="twitter:description" content="'.$expectedDescription.'">', false);

        $this->assertLessThanOrEqual(160, mb_strlen($expectedDescription));
        $this->assertStringContainsString('Program pembinaan UMKM Kota Bekasi', $expectedDescription);
        $this->assertStringStartsNotWith('<p>', $expectedDescription);
    }
}
