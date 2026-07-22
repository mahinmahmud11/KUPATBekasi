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
        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Produk yang dicari belum tersedia.')
            ->assertDontSee('Hapus filter')
            ->assertDontSee('Reset filter');
    }

    public function test_catalog_empty_state_with_active_filter(): void
    {
        $this->withoutVite();
        $category = Category::factory()->create(['name' => 'Kategori Pangan', 'slug' => 'kategori-pangan']);
        $partner = Partner::factory()->create(['name' => 'Mitra ABC', 'slug' => 'mitra-abc']);
        Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Asli']);

        $response = $this->get(route('products.index', ['q' => 'Palsu', 'category' => 'kategori-pangan', 'partner' => 'mitra-abc']));

        $response->assertOk()
            ->assertSee('Tidak ada produk yang sesuai dengan pencarian atau filter yang dipilih (kata kunci &quot;Palsu&quot;, kategori &quot;Kategori Pangan&quot;, mitra &quot;Mitra ABC&quot;).', false)
            ->assertSee('Hapus filter')
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertDontSee('Reset filter');
    }

    public function test_catalog_empty_state_with_only_q_filter(): void
    {
        $this->withoutVite();

        $response = $this->get(route('products.index', ['q' => 'TidakAdaProdukIni']));

        $response->assertOk()
            ->assertSee('Tidak ada produk yang sesuai dengan pencarian atau filter yang dipilih (kata kunci &quot;TidakAdaProdukIni&quot;).', false)
            ->assertSee('Hapus filter')
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertDontSee('Reset filter')
            ->assertDontSee('Produk yang dicari belum tersedia.');
    }

    public function test_catalog_empty_state_with_only_partner_filter(): void
    {
        $this->withoutVite();
        Partner::factory()->create(['name' => 'Mitra ZZZ', 'slug' => 'mitra-zzz']);

        $response = $this->get(route('products.index', ['partner' => 'mitra-zzz']));

        $response->assertOk()
            ->assertSee('Tidak ada produk yang sesuai dengan pencarian atau filter yang dipilih (mitra &quot;Mitra ZZZ&quot;).', false)
            ->assertSee('Hapus filter')
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertDontSee('Reset filter')
            ->assertDontSee('Produk yang dicari belum tersedia.');
    }

    public function test_catalog_filters_partner_and_preserves_query_on_pagination(): void
    {
        $this->withoutVite();
        $wantedPartner = Partner::factory()->create(['slug' => 'mitra-satu']);
        $otherPartner = Partner::factory()->create(['slug' => 'mitra-dua']);
        $category = Category::factory()->create();
        Product::factory()->count(13)->for($wantedPartner)->for($category)->create();
        $otherProduct = Product::factory()->for($otherPartner)->for($category)->create(['name' => 'Produk Mitra Lain']);

        $response = $this->get(route('products.index', ['partner' => 'mitra-satu']));

        $response->assertOk()
            ->assertDontSee($otherProduct->name)
            ->assertSee('partner=mitra-satu', false)
            ->assertSee('partner=mitra-satu&amp;page=2', false);
    }

    public function test_catalog_filters_all_combined(): void
    {
        $this->withoutVite();
        $targetCategory = Category::factory()->create(['name' => 'Makanan', 'slug' => 'makanan']);
        $otherCategory = Category::factory()->create(['name' => 'Kerajinan', 'slug' => 'kerajinan']);
        $targetPartner = Partner::factory()->create(['name' => 'Mitra Satu', 'slug' => 'mitra-satu']);
        $otherPartner = Partner::factory()->create(['name' => 'Mitra Dua', 'slug' => 'mitra-dua']);

        $targetProduct = Product::factory()->for($targetPartner)->for($targetCategory)->create(['name' => 'Keripik Pisang']);
        $diffCategoryProduct = Product::factory()->for($targetPartner)->for($otherCategory)->create(['name' => 'Keripik Singkong']);
        $diffPartnerProduct = Product::factory()->for($otherPartner)->for($targetCategory)->create(['name' => 'Keripik Kentang']);
        $diffKeywordProduct = Product::factory()->for($targetPartner)->for($targetCategory)->create(['name' => 'Kue Bolu']);

        $response = $this->get(route('products.index', ['q' => 'Keripik', 'category' => 'makanan', 'partner' => 'mitra-satu']));

        $response->assertOk()
            ->assertSee($targetProduct->name)
            ->assertDontSee($diffCategoryProduct->name)
            ->assertDontSee($diffPartnerProduct->name)
            ->assertDontSee($diffKeywordProduct->name)
            ->assertSee('value="Keripik"', false)
            ->assertSee('<option value="makanan" selected>', false)
            ->assertSee('<option value="mitra-satu" selected>', false);
    }

    public function test_catalog_search_form_displays_correct_state_and_options(): void
    {
        $this->withoutVite();
        $category1 = Category::factory()->create(['name' => 'Kategori Satu', 'slug' => 'satu']);
        $category2 = Category::factory()->create(['name' => 'Kategori Dua', 'slug' => 'dua']);

        $partner1 = Partner::factory()->create(['name' => 'Mitra Aktif', 'slug' => 'mitra-aktif']);
        $partner2 = Partner::factory()->create(['name' => 'Mitra Dua', 'slug' => 'mitra-dua']);
        $inactivePartner = Partner::factory()->create(['name' => 'Mitra Nonaktif', 'slug' => 'mitra-nonaktif', 'is_active' => false]);

        Product::factory()->for($partner1)->for($category1)->create(['name' => 'Keripik Tempe']);
        Product::factory()->for($partner2)->for($category2)->create(['name' => 'Keripik Singkong']);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('action="'.route('products.index').'"', false)
            ->assertSee('method="GET"', false)
            ->assertSee('name="q"', false)
            ->assertSee('placeholder="Cari produk atau nama UMKM"', false)
            ->assertSee('name="category"', false)
            ->assertSee('<option value="satu" >Kategori Satu</option>', false)
            ->assertSee('<option value="dua" >Kategori Dua</option>', false)
            ->assertSee('name="partner"', false)
            ->assertSee('<option value="mitra-aktif" >Mitra Aktif</option>', false)
            ->assertSee('<option value="mitra-dua" >Mitra Dua</option>', false)
            ->assertDontSee('Mitra Nonaktif')
            ->assertDontSee('Reset filter');

        $this->get(route('products.index', ['q' => 'Keripik', 'category' => 'dua', 'partner' => 'mitra-dua']))
            ->assertOk()
            ->assertSee('value="Keripik"', false)
            ->assertSee('<option value="dua" selected>Kategori Dua</option>', false)
            ->assertSee('<option value="mitra-dua" selected>Mitra Dua</option>', false)
            ->assertSee('Reset filter')
            ->assertSee('href="'.route('products.index').'"', false);

        $this->get(route('products.index', ['partner' => 'mitra-aktif']))
            ->assertOk()
            ->assertSee('<option value="mitra-aktif" selected>Mitra Aktif</option>', false)
            ->assertSee('Reset filter');
    }
}
