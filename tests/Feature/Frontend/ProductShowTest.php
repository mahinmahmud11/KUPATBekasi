<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_product_displays_relations_media_and_ordered_gallery(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $partner = Partner::factory()->create(['name' => 'Mitra Produk']);
        $category = Category::factory()->create(['name' => 'Kategori Produk']);
        $product = Product::factory()->for($partner)->for($category)->create([
            'name' => 'Produk Detail',
            'description' => 'Deskripsi lengkap produk detail.',
            'price' => 125000,
            'unit' => 'paket',
            'main_image_path' => 'products/main.webp',
        ]);
        Storage::disk('public')->put('products/main.webp', 'main');
        Storage::disk('public')->put('products/first.webp', 'first');
        Storage::disk('public')->put('products/second.webp', 'second');
        ProductImage::factory()->for($product)->create(['image_path' => 'products/second.webp', 'alt_text' => 'Galeri Kedua', 'sort_order' => 2]);
        ProductImage::factory()->for($product)->create(['image_path' => 'products/first.webp', 'alt_text' => 'Galeri Pertama', 'sort_order' => 1]);
        ProductImage::factory()->for($product)->create(['image_path' => 'products/main.webp', 'alt_text' => 'Duplikat Gambar Utama', 'sort_order' => 3]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee('<nav class="mb-6 min-w-0" aria-label="Breadcrumb">', false)
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertSee('<h1 class="mt-4 break-words text-3xl font-bold tracking-tight sm:text-4xl">'.$product->name.'</h1>', false)
            ->assertSee('<meta property="og:title" content="'.$product->name.' | '.config('app.name').'">', false)
            ->assertSee('<meta name="twitter:title" content="'.$product->name.' | '.config('app.name').'">', false)
            ->assertSee('<meta property="og:url" content="'.route('products.show', $product).'">', false)
            ->assertSee('<link rel="canonical" href="'.route('products.show', $product).'">', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url('products/main.webp')).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.url(Storage::disk('public')->url('products/main.webp')).'">', false)
            ->assertSee('data-product-category-badge', false)
            ->assertSee('href="'.route('categories.show', $category).'"', false)
            ->assertSee('data-product-stock-badge', false)
            ->assertSee('Tersedia')
            ->assertSee('Rp 125.000')
            ->assertSee('/ paket')
            ->assertSee($product->description)
            ->assertSee($partner->name)
            ->assertSee($category->name)
            ->assertSee(Storage::disk('public')->url('products/main.webp'))
            ->assertSeeInOrder(['Produk Detail', 'Galeri Pertama', 'Galeri Kedua'])
            ->assertSee('data-product-gallery', false)
            ->assertSee('data-gallery-active-image', false)
            ->assertSee('data-gallery-preview-open', false)
            ->assertSee('data-gallery-dialog', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('data-gallery-preview-close', false)
            ->assertSee('data-gallery-previous', false)
            ->assertSee('data-gallery-next', false)
            ->assertSee('aria-label="Tampilkan gambar sebelumnya"', false)
            ->assertSee('aria-label="Tampilkan gambar berikutnya"', false)
            ->assertSee('data-gallery-src="'.Storage::disk('public')->url('products/main.webp').'"', false)
            ->assertSee('data-gallery-alt="Produk Detail"', false)
            ->assertSee('data-gallery-src="'.Storage::disk('public')->url('products/first.webp').'"', false)
            ->assertSee('data-gallery-alt="Galeri Pertama"', false)
            ->assertSee('data-gallery-src="'.Storage::disk('public')->url('products/second.webp').'"', false)
            ->assertSee('data-gallery-alt="Galeri Kedua"', false)
            ->assertSee('aria-current="true"', false)
            ->assertSee('aria-current="false"', false)
            ->assertDontSee('Duplikat Gambar Utama');
    }

    public function test_stock_status_labels_are_mapped_and_short_description_is_used_as_fallback(): void
    {
        $this->withoutVite();

        $statuses = [
            'available' => 'Tersedia',
            'preorder' => 'Pre-order',
            'unavailable' => 'Tidak tersedia',
        ];

        foreach ($statuses as $status => $label) {
            $product = Product::factory()->create([
                'name' => 'Produk '.$label,
                'description' => null,
                'short_description' => 'Ringkasan produk '.$label.'.',
                'stock_status' => $status,
            ]);
            $count = Product::query()->count();

            $this->get(route('products.show', $product))
                ->assertOk()
                ->assertSee('data-product-stock-badge', false)
                ->assertSee($label)
                ->assertSee($product->short_description);

            $this->assertSame($count, Product::query()->count());
        }
    }

    public function test_gallery_uses_first_ordered_image_when_main_image_is_missing_and_removes_duplicates(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $product = Product::factory()->create(['name' => 'Produk Galeri', 'main_image_path' => null]);
        ProductImage::factory()->for($product)->create(['image_path' => 'products/later.webp', 'alt_text' => 'Gambar Lanjutan', 'sort_order' => 20]);
        ProductImage::factory()->for($product)->create(['image_path' => 'products/first.webp', 'alt_text' => 'Gambar Pertama', 'sort_order' => 10]);
        ProductImage::factory()->for($product)->create(['image_path' => 'products/first.webp', 'alt_text' => 'Duplikat', 'sort_order' => 30]);

        $response = $this->get(route('products.show', $product));

        $response
            ->assertOk()
            ->assertSee('data-gallery-active-image', false)
            ->assertSee('src="'.Storage::disk('public')->url('products/first.webp').'" alt="Gambar Pertama" data-gallery-active-image', false)
            ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url('products/first.webp')).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.url(Storage::disk('public')->url('products/first.webp')).'">', false)
            ->assertSeeInOrder(['Gambar Pertama', 'Gambar Lanjutan'])
            ->assertDontSee('Duplikat');
    }

    public function test_product_metadata_uses_site_logo_fallback_and_normalizes_description(): void
    {
        $this->withoutVite();
        Storage::fake('public');

        $logoPath = 'site-settings/social-logo.webp';
        Storage::disk('public')->put($logoPath, 'logo');
        SiteSetting::factory()->create(['logo_path' => $logoPath]);
        $description = '<strong>'.str_repeat('Deskripsi produk panjang   ', 12).'</strong>';
        $product = Product::factory()->create([
            'short_description' => null,
            'description' => $description,
            'main_image_path' => null,
        ]);
        $counts = [Product::query()->count(), SiteSetting::query()->count()];
        $expectedDescription = Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags($description))), 160, '');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('<meta name="description" content="'.$expectedDescription.'">', false)
            ->assertSee('<meta property="og:description" content="'.$expectedDescription.'">', false)
            ->assertSee('<meta name="twitter:description" content="'.$expectedDescription.'">', false)
            ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url($logoPath)).'">', false)
            ->assertSee('<meta name="twitter:image" content="'.url(Storage::disk('public')->url($logoPath)).'">', false);

        $this->assertLessThanOrEqual(160, mb_strlen($expectedDescription));
        $this->assertSame($counts, [Product::query()->count(), SiteSetting::query()->count()]);
    }

    public function test_single_image_gallery_hides_navigation_buttons(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['main_image_path' => 'products/only.webp']);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-product-gallery', false)
            ->assertSee('data-gallery-thumbnail', false)
            ->assertDontSee('data-gallery-previous', false)
            ->assertDontSee('data-gallery-next', false);
    }

    public function test_product_without_media_displays_fallback(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['main_image_path' => null]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Gambar belum tersedia')
            ->assertDontSee('data-product-gallery', false)
            ->assertDontSee('<meta property="og:image"', false)
            ->assertDontSee('<meta name="twitter:image"', false);
    }

    public function test_non_public_product_returns_not_found(): void
    {
        $this->withoutVite();
        $inactive = Product::factory()->create(['is_active' => false]);
        $deleted = Product::factory()->create();
        $deleted->delete();

        $this->get(route('products.show', $inactive))->assertNotFound();
        $this->get('/produk/'.$deleted->slug)->assertNotFound();
    }

    public function test_whatsapp_url_is_normalized_and_encoded(): void
    {
        $this->withoutVite();
        $partner = Partner::factory()->create(['whatsapp' => '0812-3456-7890']);
        $product = Product::factory()->for($partner)->create(['name' => 'Produk & Spesial']);

        $count = Product::query()->count();
        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee('https://wa.me/6281234567890?text=', false)
            ->assertSee('Produk%20%26%20Spesial', false)
            ->assertSee('data-mobile-whatsapp-cta', false)
            ->assertSee('class="fixed inset-x-4 bottom-4 z-40 sm:hidden"', false)
            ->assertSee('Tanya Produk via WhatsApp');

        $whatsappUrl = 'https://wa.me/6281234567890?text=Halo%2C%20saya%20melihat%20produk%20Produk%20%26%20Spesial%20di%20KUPATBekasi.%20Apakah%20produk%20ini%20masih%20tersedia%3F';

        $this->assertSame(2, substr_count($response->getContent(), 'href="'.$whatsappUrl.'"'));
        $this->assertSame($count, Product::query()->count());
    }

    public function test_whatsapp_buttons_are_hidden_for_invalid_or_empty_number(): void
    {
        $this->withoutVite();

        foreach (['tidak-valid', ''] as $whatsapp) {
            $product = Product::factory()->for(Partner::factory()->state(['whatsapp' => $whatsapp]))->create();
            $count = Product::query()->count();

            $this->get(route('products.show', $product))
                ->assertOk()
                ->assertDontSee('Hubungi via WhatsApp')
                ->assertDontSee('Tanya Produk via WhatsApp')
                ->assertDontSee('data-mobile-whatsapp-cta', false);

            $this->assertSame($count, Product::query()->count());
        }
    }
}
