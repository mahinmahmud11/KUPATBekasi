<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_category_only_displays_its_public_products(): void
    {
        $this->withoutVite();
        $category = Category::factory()->create(['name' => 'Kategori Pilihan']);
        $otherCategory = Category::factory()->create();
        $partner = Partner::factory()->create();
        $active = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Kategori Aktif']);
        $inactive = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Kategori Nonaktif', 'is_active' => false]);
        $other = Product::factory()->for($partner)->for($otherCategory)->create(['name' => 'Produk Kategori Lain']);

        $this->get(route('categories.show', $category))->assertOk()->assertSee($category->name)
            ->assertSee($active->name)->assertDontSee($inactive->name)->assertDontSee($other->name);
    }

    public function test_inactive_category_returns_not_found(): void
    {
        $this->withoutVite();
        $category = Category::factory()->create(['is_active' => false]);
        $this->get(route('categories.show', $category))->assertNotFound();
    }

    public function test_category_has_pagination_and_empty_state(): void
    {
        $this->withoutVite();
        $category = Category::factory()->create();
        $this->get(route('categories.show', $category))->assertSee('Produk aktif dalam kategori ini belum tersedia.');

        $partner = Partner::factory()->create();
        Product::factory()->count(13)->for($partner)->for($category)->create();
        $this->get(route('categories.show', $category))->assertOk()->assertSee('page=2', false);
    }
}
