<?php

namespace Tests\Feature\Frontend;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_public_url_displays_safe_custom_not_found_page_without_mutating_data(): void
    {
        $this->withoutVite();

        $partner = Partner::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($partner)->for($category)->create();
        ProductImage::factory()->for($product)->create();
        Banner::factory()->create();
        SiteSetting::factory()->create();
        User::factory()->create();

        $before = $this->recordCounts();

        $response = $this->get('/halaman-yang-tidak-tersedia');

        $response
            ->assertNotFound()
            ->assertSee('<title>Halaman Tidak Ditemukan', false)
            ->assertSee('<meta name="description" content="Halaman yang Anda cari tidak ditemukan di KUPATBekasi.">', false)
            ->assertSee('<h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Halaman tidak ditemukan</h1>', false)
            ->assertSee('Halaman yang Anda tuju mungkin sudah dipindahkan atau tidak tersedia.')
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('Kembali ke Beranda')
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertSee('Lihat Katalog Produk')
            ->assertSee('<header>', false)
            ->assertSee('<main>', false)
            ->assertSee('<footer>', false)
            ->assertDontSee('Stack trace')
            ->assertDontSee('Exception')
            ->assertDontSee('APP_KEY')
            ->assertDontSee(base_path(), false);

        $this->assertSame($before, $this->recordCounts());
    }

    private function recordCounts(): array
    {
        return [
            'banners' => Banner::query()->count(),
            'categories' => Category::query()->count(),
            'partners' => Partner::withTrashed()->count(),
            'product_images' => ProductImage::query()->count(),
            'products' => Product::withTrashed()->count(),
            'site_settings' => SiteSetting::query()->count(),
            'users' => User::query()->count(),
        ];
    }
}
