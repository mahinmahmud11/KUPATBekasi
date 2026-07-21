<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $product = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Detail', 'main_image_path' => 'products/main.webp']);
        Storage::disk('public')->put('products/main.webp', 'main');
        Storage::disk('public')->put('products/first.webp', 'first');
        Storage::disk('public')->put('products/second.webp', 'second');
        ProductImage::factory()->for($product)->create(['image_path' => 'products/second.webp', 'alt_text' => 'Galeri Kedua', 'sort_order' => 2]);
        ProductImage::factory()->for($product)->create(['image_path' => 'products/first.webp', 'alt_text' => 'Galeri Pertama', 'sort_order' => 1]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk()->assertSee($product->name)->assertSee($partner->name)->assertSee($category->name)
            ->assertSee(Storage::disk('public')->url('products/main.webp'))
            ->assertSeeInOrder(['Galeri Pertama', 'Galeri Kedua']);
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

        $this->get(route('products.show', $product))->assertOk()
            ->assertSee('https://wa.me/6281234567890?text=', false)
            ->assertSee('Produk%20%26%20Spesial', false);
    }

    public function test_whatsapp_button_is_hidden_for_invalid_number(): void
    {
        $this->withoutVite();
        $product = Product::factory()->for(Partner::factory()->state(['whatsapp' => 'tidak-valid']))->create();

        $this->get(route('products.show', $product))->assertOk()->assertDontSee('Hubungi via WhatsApp');
    }
}
