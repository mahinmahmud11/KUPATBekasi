<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_route_only_displays_publicly_valid_products(): void
    {
        $this->withoutVite();
        $category = Category::factory()->create();
        $partner = Partner::factory()->create();
        $active = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Aktif']);
        $inactive = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Nonaktif', 'is_active' => false]);
        $deleted = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Terhapus']);
        $deleted->delete();
        $inactivePartner = Partner::factory()->create(['is_active' => false]);
        $hiddenByPartner = Product::factory()->for($inactivePartner)->for($category)->create(['name' => 'Produk Mitra Nonaktif']);

        $this->assertSame('/produk', route('products.index', absolute: false));
        $this->get(route('products.index'))->assertOk()->assertSee($active->name)
            ->assertDontSee($inactive->name)->assertDontSee($deleted->name)->assertDontSee($hiddenByPartner->name);
    }

    public function test_catalog_searches_product_partner_and_short_description(): void
    {
        $this->withoutVite();
        $category = Category::factory()->create();
        $partner = Partner::factory()->create(['name' => 'Mitra Patriot']);
        $byName = Product::factory()->for($partner)->for($category)->create(['name' => 'Keripik Pencarian']);
        $byDescription = Product::factory()->for(Partner::factory())->for($category)->create(['name' => 'Produk Kedua', 'short_description' => 'Rasa pala istimewa']);
        $other = Product::factory()->for(Partner::factory())->for($category)->create(['name' => 'Produk Lain']);

        $this->get(route('products.index', ['q' => 'Keripik']))->assertSee($byName->name)->assertDontSee($other->name);
        $this->get(route('products.index', ['q' => 'Patriot']))->assertSee($byName->name);
        $this->get(route('products.index', ['q' => 'pala']))->assertSee($byDescription->name);
    }

    public function test_catalog_filters_category_and_preserves_query_on_pagination(): void
    {
        $this->withoutVite();
        $wantedCategory = Category::factory()->create(['slug' => 'makanan']);
        $otherCategory = Category::factory()->create(['slug' => 'kerajinan']);
        $partner = Partner::factory()->create();
        Product::factory()->count(13)->for($partner)->for($wantedCategory)->create();
        $other = Product::factory()->for($partner)->for($otherCategory)->create(['name' => 'Produk Kategori Lain']);

        $response = $this->get(route('products.index', ['q' => '', 'category' => 'makanan']));

        $response->assertOk()->assertDontSee($other->name)->assertSee('category=makanan', false);
    }

    public function test_catalog_has_an_empty_state(): void
    {
        $this->withoutVite();
        $this->get(route('products.index'))->assertOk()->assertSee('Produk yang dicari belum tersedia.');
    }
}
