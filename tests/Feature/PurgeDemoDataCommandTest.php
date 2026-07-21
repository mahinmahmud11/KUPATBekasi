<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\KupatBekasiDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeDemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_can_be_cancelled_without_changing_data(): void
    {
        $this->seed(KupatBekasiDemoSeeder::class);
        $before = $this->recordCounts();

        $this->artisan('kupat:purge-demo-data')
            ->expectsConfirmation('Hapus seluruh data demo KUPATBekasi yang teridentifikasi?', 'no')
            ->expectsOutput('Pembersihan dibatalkan. Tidak ada data yang diubah.')
            ->assertSuccessful();

        $this->assertSame($before, $this->recordCounts());
    }

    public function test_force_purges_only_demo_records_and_preserves_categories_users_and_site_media(): void
    {
        Storage::fake('public');
        $this->seed(KupatBekasiDemoSeeder::class);

        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'makanan')->firstOrFail();
        $realPartner = Partner::factory()->create(['slug' => 'mitra-asli']);
        $realProduct = Product::factory()->for($realPartner)->for($category)->create(['slug' => 'produk-asli']);
        $realBanner = Banner::factory()->create(['title' => 'Banner Asli', 'sort_order' => 99]);

        $siteSetting = SiteSetting::query()->findOrFail(1);
        Storage::disk('public')->put('site-settings/logo.png', 'logo');
        Storage::disk('public')->put('site-settings/favicon.png', 'favicon');
        $siteSetting->update([
            'logo_path' => 'site-settings/logo.png',
            'favicon_path' => 'site-settings/favicon.png',
        ]);

        $this->artisan('kupat:purge-demo-data', ['--force' => true])
            ->expectsOutput('Product dihapus: 30')
            ->expectsOutput('Product ber-slug demo dipertahankan: 0')
            ->expectsOutput('Partner dihapus: 10')
            ->expectsOutput('Partner ber-slug demo dipertahankan: 0')
            ->expectsOutput('Banner dihapus: 2')
            ->expectsOutput('Field SiteSetting dibersihkan: 3')
            ->expectsOutput('Category dipertahankan: 6')
            ->expectsOutput('User tidak disentuh: 1')
            ->assertSuccessful();

        $this->assertSame(0, Product::withTrashed()->where('slug', 'keripik-singkong-rempah')->count());
        $this->assertSame(0, Partner::withTrashed()->where('slug', 'dapur-patriot-rasa')->count());
        $this->assertDatabaseMissing('banners', ['title' => 'Bangga Produk UMKM Kota Bekasi']);
        $this->assertDatabaseMissing('banners', ['title' => 'Jelajahi Karya Mitra Lokal']);
        $this->assertSame(6, Category::query()->count());
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('partners', ['id' => $realPartner->id]);
        $this->assertDatabaseHas('products', ['id' => $realProduct->id]);
        $this->assertDatabaseHas('banners', ['id' => $realBanner->id]);

        $siteSetting->refresh();
        $this->assertNull($siteSetting->about_summary);
        $this->assertNull($siteSetting->contact_whatsapp);
        $this->assertNull($siteSetting->contact_email);
        $this->assertSame('KUPATBekasi', $siteSetting->site_name);
        $this->assertSame('Katalog UMKM Patriot Binaan Kota Bekasi', $siteSetting->tagline);
        $this->assertSame('Kota Bekasi, Jawa Barat', $siteSetting->address);
        $this->assertSame('site-settings/logo.png', $siteSetting->logo_path);
        $this->assertSame('site-settings/favicon.png', $siteSetting->favicon_path);
        Storage::disk('public')->assertExists('site-settings/logo.png');
        Storage::disk('public')->assertExists('site-settings/favicon.png');
    }

    public function test_non_demo_site_setting_values_are_not_overwritten(): void
    {
        $this->seed(KupatBekasiDemoSeeder::class);
        $siteSetting = SiteSetting::query()->findOrFail(1);
        $siteSetting->update([
            'about_summary' => 'Informasi resmi.',
            'contact_whatsapp' => '6281234567890',
            'contact_email' => 'resmi@example.test',
        ]);

        $this->artisan('kupat:purge-demo-data', ['--force' => true])
            ->expectsOutput('Field SiteSetting dibersihkan: 0')
            ->assertSuccessful();

        $siteSetting->refresh();
        $this->assertSame('Informasi resmi.', $siteSetting->about_summary);
        $this->assertSame('6281234567890', $siteSetting->contact_whatsapp);
        $this->assertSame('resmi@example.test', $siteSetting->contact_email);
    }

    public function test_product_with_demo_slug_and_official_content_is_retained_with_its_partner(): void
    {
        $this->seed(KupatBekasiDemoSeeder::class);
        $product = Product::query()->where('slug', 'keripik-singkong-rempah')->firstOrFail();
        $partner = $product->partner;
        $product->update([
            'short_description' => 'Deskripsi singkat resmi.',
            'description' => 'Deskripsi lengkap resmi.',
        ]);

        $this->artisan('kupat:purge-demo-data', ['--force' => true])
            ->expectsOutput('Product dihapus: 29')
            ->expectsOutput('Product ber-slug demo dipertahankan: 1')
            ->expectsOutput('Partner dihapus: 9')
            ->expectsOutput('Partner ber-slug demo dipertahankan: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('partners', ['id' => $partner->id]);
    }

    public function test_partner_with_demo_slug_and_official_fingerprint_is_retained(): void
    {
        $this->seed(KupatBekasiDemoSeeder::class);
        $partner = Partner::query()->where('slug', 'kedai-embun-bekasi')->firstOrFail();
        $partner->update([
            'short_description' => 'Profil usaha resmi.',
            'description' => 'Deskripsi usaha resmi.',
            'whatsapp' => '6281234567890',
        ]);

        $this->artisan('kupat:purge-demo-data', ['--force' => true])
            ->expectsOutput('Product dihapus: 30')
            ->expectsOutput('Partner dihapus: 9')
            ->expectsOutput('Partner ber-slug demo dipertahankan: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('partners', ['id' => $partner->id, 'whatsapp' => '6281234567890']);
    }

    public function test_site_setting_with_non_default_id_is_cleaned_without_removing_media(): void
    {
        Storage::fake('public');
        $this->seed(KupatBekasiDemoSeeder::class);
        SiteSetting::query()->firstOrFail()->delete();
        Storage::disk('public')->put('site-settings/official-logo.png', 'logo');
        Storage::disk('public')->put('site-settings/official-favicon.png', 'favicon');
        $siteSetting = SiteSetting::query()->forceCreate([
            'id' => 42,
            'site_name' => 'KUPATBekasi',
            'tagline' => 'Katalog UMKM Patriot Binaan Kota Bekasi',
            'about_summary' => 'Data demonstrasi katalog digital untuk memperkenalkan produk UMKM lokal Kota Bekasi.',
            'contact_whatsapp' => '628000000000',
            'contact_email' => 'demo@kupatbekasi.test',
            'address' => 'Kota Bekasi, Jawa Barat',
            'logo_path' => 'site-settings/official-logo.png',
            'favicon_path' => 'site-settings/official-favicon.png',
        ]);

        $this->artisan('kupat:purge-demo-data', ['--force' => true])
            ->expectsOutput('Field SiteSetting dibersihkan: 3')
            ->assertSuccessful();

        $siteSetting->refresh();
        $this->assertSame(42, $siteSetting->id);
        $this->assertNull($siteSetting->about_summary);
        $this->assertNull($siteSetting->contact_whatsapp);
        $this->assertNull($siteSetting->contact_email);
        $this->assertSame('site-settings/official-logo.png', $siteSetting->logo_path);
        $this->assertSame('site-settings/official-favicon.png', $siteSetting->favicon_path);
        Storage::disk('public')->assertExists('site-settings/official-logo.png');
        Storage::disk('public')->assertExists('site-settings/official-favicon.png');
    }

    public function test_command_is_idempotent(): void
    {
        $this->seed(KupatBekasiDemoSeeder::class);

        $this->artisan('kupat:purge-demo-data', ['--force' => true])->assertSuccessful();
        $this->artisan('kupat:purge-demo-data', ['--force' => true])
            ->expectsOutput('Product dihapus: 0')
            ->expectsOutput('Product ber-slug demo dipertahankan: 0')
            ->expectsOutput('Partner dihapus: 0')
            ->expectsOutput('Partner ber-slug demo dipertahankan: 0')
            ->expectsOutput('Banner dihapus: 0')
            ->expectsOutput('Field SiteSetting dibersihkan: 0')
            ->assertSuccessful();
    }

    public function test_soft_deleted_demo_records_and_their_media_are_permanently_removed(): void
    {
        Storage::fake('public');
        $this->seed(KupatBekasiDemoSeeder::class);

        $product = Product::query()->where('slug', 'keripik-singkong-rempah')->firstOrFail();
        $partner = Partner::query()->where('slug', 'dapur-patriot-rasa')->firstOrFail();
        $banner = Banner::query()->where('title', 'Bangga Produk UMKM Kota Bekasi')->firstOrFail();

        Storage::disk('public')->put('products/demo-main.webp', 'main');
        Storage::disk('public')->put('products/demo-gallery.webp', 'gallery');
        Storage::disk('public')->put('partners/demo-logo.webp', 'logo');
        Storage::disk('public')->put('partners/demo-cover.webp', 'cover');
        Storage::disk('public')->put('banners/demo.webp', 'banner');

        $product->update(['main_image_path' => 'products/demo-main.webp']);
        ProductImage::factory()->for($product)->create(['image_path' => 'products/demo-gallery.webp']);
        $partner->update(['logo_path' => 'partners/demo-logo.webp', 'cover_path' => 'partners/demo-cover.webp']);
        $banner->update(['image_path' => 'banners/demo.webp']);
        $product->delete();
        $partner->delete();

        $this->artisan('kupat:purge-demo-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Product::withTrashed()->whereKey($product->id)->count());
        $this->assertSame(0, Partner::withTrashed()->whereKey($partner->id)->count());
        Storage::disk('public')->assertMissing('products/demo-main.webp');
        Storage::disk('public')->assertMissing('products/demo-gallery.webp');
        Storage::disk('public')->assertMissing('partners/demo-logo.webp');
        Storage::disk('public')->assertMissing('partners/demo-cover.webp');
        Storage::disk('public')->assertMissing('banners/demo.webp');
    }

    private function recordCounts(): array
    {
        return [
            'products' => Product::withTrashed()->count(),
            'partners' => Partner::withTrashed()->count(),
            'banners' => Banner::query()->count(),
            'categories' => Category::query()->count(),
            'site_settings' => SiteSetting::query()->count(),
            'users' => User::query()->count(),
        ];
    }
}
