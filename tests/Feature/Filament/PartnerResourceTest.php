<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Filament\Resources\Partners\PartnerResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_the_partner_list(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(PartnerResource::getUrl('index'))
            ->assertOk();
    }

    public function test_regular_user_cannot_open_the_partner_resource(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(PartnerResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_administrator_can_create_a_partner(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePartner::class)
            ->fillForm($this->validPartnerData())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('partners', [
            'name' => 'Mitra Uji Bekasi',
            'slug' => 'mitra-uji-bekasi',
            'whatsapp' => '628000009999',
            'is_featured' => true,
            'is_active' => true,
        ]);
    }

    public function test_slug_is_generated_from_name_when_slug_is_empty(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePartner::class)
            ->set('data.slug', '')
            ->set('data.name', 'Kriya Patriot Baru')
            ->assertSet('data.slug', 'kriya-patriot-baru');
    }

    public function test_partner_name_is_required(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), ['name' => null]))
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_short_description_accepts_and_preserves_500_characters(): void
    {
        $description = str_repeat('A', 500);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), [
                'short_description' => $description,
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $partner = Partner::query()->where('slug', 'mitra-uji-bekasi')->firstOrFail();

        $this->assertSame(500, strlen($partner->short_description));
        $this->assertSame($description, $partner->short_description);
    }

    public function test_short_description_rejects_501_characters(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), [
                'short_description' => str_repeat('A', 501),
            ]))
            ->call('create')
            ->assertHasFormErrors(['short_description' => 'max']);
    }

    public function test_partner_slug_must_be_unique(): void
    {
        Partner::factory()->create(['slug' => 'mitra-uji-bekasi']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePartner::class)
            ->fillForm($this->validPartnerData())
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_whatsapp_must_start_with_62(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), ['whatsapp' => '08000009999']))
            ->call('create')
            ->assertHasFormErrors(['whatsapp']);
    }

    public function test_whatsapp_rejects_letters_and_plus_sign(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), ['whatsapp' => '62abc0009999']))
            ->call('create')
            ->assertHasFormErrors(['whatsapp']);

        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), ['whatsapp' => '+628000009999']))
            ->call('create')
            ->assertHasFormErrors(['whatsapp']);
    }

    public function test_instagram_rejects_an_invalid_url(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), ['instagram_url' => 'bukan-url']))
            ->call('create')
            ->assertHasFormErrors(['instagram_url' => 'url']);
    }

    public function test_administrator_can_update_a_partner(): void
    {
        $partner = Partner::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditPartner::class, ['record' => $partner->getRouteKey()])
            ->fillForm(array_merge($this->validPartnerData(), [
                'name' => 'Mitra Diperbarui',
                'slug' => 'mitra-diperbarui',
                'is_active' => false,
                'sort_order' => 12,
            ]))
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'name' => 'Mitra Diperbarui',
            'slug' => 'mitra-diperbarui',
            'is_active' => false,
            'sort_order' => 12,
        ]);
    }

    public function test_partner_search_works(): void
    {
        $wanted = Partner::factory()->create(['district' => 'Bekasi Barat']);
        $hidden = Partner::factory()->create(['district' => 'Rawalumbu']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListPartners::class)
            ->searchTable('Bekasi Barat')
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_active_status_filter_works(): void
    {
        $active = Partner::factory()->create(['is_active' => true]);
        $inactive = Partner::factory()->create(['is_active' => false]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListPartners::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_featured_status_filter_works(): void
    {
        $featured = Partner::factory()->create(['is_featured' => true]);
        $regular = Partner::factory()->create(['is_featured' => false]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListPartners::class)
            ->filterTable('is_featured', true)
            ->assertCanSeeTableRecords([$featured])
            ->assertCanNotSeeTableRecords([$regular]);
    }

    public function test_empty_partner_can_be_soft_deleted(): void
    {
        $partner = Partner::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditPartner::class, ['record' => $partner->getRouteKey()])
            ->callAction('delete');

        $this->assertSoftDeleted($partner);
    }

    public function test_partner_with_products_cannot_be_deleted(): void
    {
        $partner = Partner::factory()->create();
        Product::factory()->create([
            'partner_id' => $partner->id,
            'category_id' => Category::factory(),
        ]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditPartner::class, ['record' => $partner->getRouteKey()])
            ->assertActionHidden('delete');

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'deleted_at' => null,
        ]);
    }

    public function test_product_count_is_displayed_without_error(): void
    {
        $partner = Partner::factory()->create();
        Product::factory()->count(2)->create([
            'partner_id' => $partner->id,
            'category_id' => Category::factory(),
        ]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListPartners::class)
            ->assertCanSeeTableRecords([$partner])
            ->assertTableColumnStateSet('products_count', 2, $partner);
    }

    public function test_logo_and_cover_paths_are_not_changed_through_the_form(): void
    {
        $partner = Partner::factory()->create([
            'logo_path' => 'partners/logos/existing.webp',
            'cover_path' => 'partners/covers/existing.webp',
        ]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditPartner::class, ['record' => $partner->getRouteKey()])
            ->fillForm(array_merge($this->validPartnerData(), [
                'name' => 'Mitra Tanpa Perubahan Media',
                'slug' => 'mitra-tanpa-perubahan-media',
            ]))
            ->call('save')
            ->assertHasNoFormErrors();

        $partner->refresh();
        $this->assertSame('partners/logos/existing.webp', $partner->logo_path);
        $this->assertSame('partners/covers/existing.webp', $partner->cover_path);
    }

    public function test_demo_dataset_remains_complete_after_normal_seeding(): void
    {
        $this->seed();

        $this->assertSame(6, Category::query()->count());
        $this->assertSame(10, Partner::query()->count());
        $this->assertSame(30, Product::query()->count());
        $this->assertSame(2, Banner::query()->count());
        $this->assertSame(1, SiteSetting::query()->count());
        $this->assertSame(0, User::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function validPartnerData(): array
    {
        return [
            'name' => 'Mitra Uji Bekasi',
            'slug' => 'mitra-uji-bekasi',
            'owner_name' => 'Pemilik Demo',
            'short_description' => 'Profil mitra fiktif untuk pengujian resource.',
            'description' => 'Deskripsi lengkap mitra fiktif untuk kebutuhan pengujian.',
            'address' => 'Kawasan Uji, Kota Bekasi',
            'district' => 'Bekasi Timur',
            'whatsapp' => '628000009999',
            'instagram_url' => 'https://example.test/mitra-uji',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 8,
        ];
    }
}
