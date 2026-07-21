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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_valid_logo_and_cover_can_be_uploaded_and_paths_saved(): void
    {
        Storage::fake('public');
        $logo = UploadedFile::fake()->image('logo.jpg')->size(1024);
        $cover = UploadedFile::fake()->image('cover.jpg')->size(1024);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), [
                'logo_path' => $logo,
                'cover_path' => $cover,
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $partner = Partner::query()->latest('id')->first();
        $this->assertNotNull($partner->logo_path);
        $this->assertStringStartsWith('partners/logos/', $partner->logo_path);
        Storage::disk('public')->assertExists($partner->logo_path);

        $this->assertNotNull($partner->cover_path);
        $this->assertStringStartsWith('partners/covers/', $partner->cover_path);
        Storage::disk('public')->assertExists($partner->cover_path);
    }

    public function test_non_image_file_is_rejected_for_logo_and_cover(): void
    {
        Storage::fake('public');
        $doc = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), [
                'logo_path' => $doc,
                'cover_path' => $doc,
            ]))
            ->call('create')
            ->assertHasFormErrors(['logo_path', 'cover_path']);
    }

    public function test_file_exceeding_2mb_is_rejected_for_logo_and_cover(): void
    {
        Storage::fake('public');
        $large = UploadedFile::fake()->image('large.jpg')->size(2500);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreatePartner::class)
            ->fillForm(array_merge($this->validPartnerData(), [
                'logo_path' => $large,
                'cover_path' => $large,
            ]))
            ->call('create')
            ->assertHasFormErrors(['logo_path', 'cover_path']);
    }

    public function test_replacing_logo_via_edit_form_removes_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('partners/logos/old.webp', 'old content');
        $newFile = UploadedFile::fake()->image('new-logo.jpg')->size(1024);

        $partner = Partner::factory()->create(['logo_path' => 'partners/logos/old.webp']);

        Storage::disk('public')->assertExists('partners/logos/old.webp');

        $this->actingAs(User::factory()->admin()->create());
        $component = Livewire::test(EditPartner::class, ['record' => $partner->getRouteKey()]);

        $component->fillForm($this->validPartnerData());

        $state = $component->get('data.logo_path');
        $this->assertIsArray($state);
        $this->assertContains('partners/logos/old.webp', $state);

        $oldFileKey = array_search('partners/logos/old.webp', $state, true);
        $this->assertNotFalse($oldFileKey);

        $component->call(
            'callSchemaComponentMethod',
            'form.logo_path',
            'deleteUploadedFile',
            ['fileKey' => $oldFileKey],
        );

        $newState = $component->get('data.logo_path');
        $this->assertNotContains('partners/logos/old.webp', $newState);

        $component->set('data.logo_path', [$newFile]);
        $component->call('save')->assertHasNoFormErrors();

        $partner->refresh();

        $this->assertNotSame('partners/logos/old.webp', $partner->logo_path);
        $this->assertStringStartsWith('partners/logos/', $partner->logo_path);
        Storage::disk('public')->assertExists($partner->logo_path);
        Storage::disk('public')->assertMissing('partners/logos/old.webp');
    }

    public function test_replacing_cover_via_edit_form_removes_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('partners/covers/old.webp', 'old content');
        $newFile = UploadedFile::fake()->image('new-cover.jpg')->size(1024);

        $partner = Partner::factory()->create(['cover_path' => 'partners/covers/old.webp']);

        Storage::disk('public')->assertExists('partners/covers/old.webp');

        $this->actingAs(User::factory()->admin()->create());
        $component = Livewire::test(EditPartner::class, ['record' => $partner->getRouteKey()]);

        $component->fillForm($this->validPartnerData());

        $state = $component->get('data.cover_path');
        $this->assertIsArray($state);
        $this->assertContains('partners/covers/old.webp', $state);

        $oldFileKey = array_search('partners/covers/old.webp', $state, true);
        $this->assertNotFalse($oldFileKey);

        $component->call(
            'callSchemaComponentMethod',
            'form.cover_path',
            'deleteUploadedFile',
            ['fileKey' => $oldFileKey],
        );

        $newState = $component->get('data.cover_path');
        $this->assertNotContains('partners/covers/old.webp', $newState);

        $component->set('data.cover_path', [$newFile]);
        $component->call('save')->assertHasNoFormErrors();

        $partner->refresh();

        $this->assertNotSame('partners/covers/old.webp', $partner->cover_path);
        $this->assertStringStartsWith('partners/covers/', $partner->cover_path);
        Storage::disk('public')->assertExists($partner->cover_path);
        Storage::disk('public')->assertMissing('partners/covers/old.webp');
    }

    public function test_edit_without_new_upload_retains_logo_and_cover_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('partners/logos/existing.webp', 'dummy');
        Storage::disk('public')->put('partners/covers/existing.webp', 'dummy');

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
        Storage::disk('public')->assertExists('partners/logos/existing.webp');
        Storage::disk('public')->assertExists('partners/covers/existing.webp');
    }

    public function test_soft_delete_retains_logo_and_cover_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('partners/logos/existing.webp', 'dummy');
        Storage::disk('public')->put('partners/covers/existing.webp', 'dummy');

        $partner = Partner::factory()->create([
            'logo_path' => 'partners/logos/existing.webp',
            'cover_path' => 'partners/covers/existing.webp',
        ]);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(EditPartner::class, ['record' => $partner->getRouteKey()])
            ->callAction('delete');

        $this->assertSoftDeleted($partner);
        Storage::disk('public')->assertExists('partners/logos/existing.webp');
        Storage::disk('public')->assertExists('partners/covers/existing.webp');
    }

    public function test_force_delete_removes_logo_and_cover_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('partners/logos/existing.webp', 'dummy');
        Storage::disk('public')->put('partners/covers/existing.webp', 'dummy');

        $partner = Partner::factory()->create([
            'logo_path' => 'partners/logos/existing.webp',
            'cover_path' => 'partners/covers/existing.webp',
        ]);

        $partner->forceDelete();

        $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
        Storage::disk('public')->assertMissing('partners/logos/existing.webp');
        Storage::disk('public')->assertMissing('partners/covers/existing.webp');
    }

    public function test_force_delete_is_safe_when_paths_are_null_or_files_missing(): void
    {
        Storage::fake('public');

        $partner1 = Partner::factory()->create(['logo_path' => null, 'cover_path' => null]);
        $partner1->forceDelete();
        $this->assertDatabaseMissing('partners', ['id' => $partner1->id]);

        $partner2 = Partner::factory()->create([
            'logo_path' => 'partners/logos/missing.webp',
            'cover_path' => 'partners/covers/missing.webp',
        ]);
        $partner2->forceDelete();
        $this->assertDatabaseMissing('partners', ['id' => $partner2->id]);
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
