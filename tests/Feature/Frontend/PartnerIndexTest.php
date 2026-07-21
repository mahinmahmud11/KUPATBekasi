<?php

namespace Tests\Feature\Frontend;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_index_only_displays_active_partners_and_searches_by_name(): void
    {
        $this->withoutVite();
        $active = Partner::factory()->create(['name' => 'Mitra Patriot Aktif']);
        $inactive = Partner::factory()->create(['name' => 'Mitra Patriot Nonaktif', 'is_active' => false]);
        $other = Partner::factory()->create(['name' => 'Usaha Lain']);

        $this->assertSame('/mitra', route('partners.index', absolute: false));
        $this->get(route('partners.index', ['q' => 'Patriot']))->assertOk()
            ->assertSee($active->name)->assertDontSee($inactive->name)->assertDontSee($other->name);
    }

    public function test_partner_index_paginates_and_has_an_empty_state(): void
    {
        $this->withoutVite();
        $this->get(route('partners.index'))->assertSee('Mitra yang dicari belum tersedia.');

        Partner::factory()->count(13)->create();
        $this->get(route('partners.index', ['q' => '']))->assertOk()->assertSee('page=2', false);
    }
}
