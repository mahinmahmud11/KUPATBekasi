<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_partner_only_displays_its_active_products(): void
    {
        $this->withoutVite();
        $partner = Partner::factory()->create(['name' => 'Mitra Profil']);
        $otherPartner = Partner::factory()->create();
        $category = Category::factory()->create();
        $active = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Mitra Aktif']);
        $inactive = Product::factory()->for($partner)->for($category)->create(['name' => 'Produk Mitra Nonaktif', 'is_active' => false]);
        $other = Product::factory()->for($otherPartner)->for($category)->create(['name' => 'Produk Mitra Lain']);

        $this->get(route('partners.show', $partner))->assertOk()->assertSee($partner->name)
            ->assertSee($active->name)->assertDontSee($inactive->name)->assertDontSee($other->name);
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
        $partner = Partner::factory()->create();

        $this->get(route('partners.show', $partner))->assertOk()->assertSee('Produk aktif dari mitra ini belum tersedia.');
    }
}
