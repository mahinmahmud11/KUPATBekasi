<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Banners\Pages\ListBanners;
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

class BannerResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_the_banner_list(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(BannerResource::getUrl('index'))->assertOk();
    }

    public function test_regular_user_cannot_open_the_banner_resource(): void
    {
        $this->actingAs(User::factory()->create())->get(BannerResource::getUrl('index'))->assertForbidden();
    }

    public function test_administrator_can_create_a_banner(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm($this->validData())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('banners', ['title' => 'Banner Uji', 'button_url' => '/produk']);
    }

    public function test_title_is_required(): void
    {
        $this->assertCreateError('title', null, 'required');
    }

    public function test_subtitle_accepts_one_thousand_characters(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreateBanner::class)
            ->fillForm(array_merge($this->validData(), ['subtitle' => str_repeat('A', 1000)]))
            ->call('create')->assertHasNoFormErrors();
    }

    public function test_subtitle_rejects_more_than_one_thousand_characters(): void
    {
        $this->assertCreateError('subtitle', str_repeat('A', 1001), 'max');
    }

    public function test_internal_path_is_accepted(): void
    {
        $this->assertButtonUrlAccepted('/produk');
    }

    public function test_https_url_is_accepted(): void
    {
        $this->assertButtonUrlAccepted('https://example.test/produk');
    }

    public function test_http_url_is_accepted(): void
    {
        $this->assertButtonUrlAccepted('http://example.test/produk');
    }

    public function test_plain_text_url_is_rejected(): void
    {
        $this->assertCreateError('button_url', 'produk');
    }

    public function test_javascript_url_is_rejected(): void
    {
        $this->assertCreateError('button_url', 'javascript:alert(1)');
    }

    public function test_data_url_is_rejected(): void
    {
        $this->assertCreateError('button_url', 'data:text/html,test');
    }

    public function test_protocol_relative_url_is_rejected(): void
    {
        $this->assertCreateError('button_url', '//example.test');
    }

    public function test_button_url_requires_button_label(): void
    {
        $this->assertCreateError('button_label', null, 'required_with');
    }

    public function test_button_label_requires_button_url(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreateBanner::class)
            ->fillForm(array_merge($this->validData(), ['button_label' => 'Lihat', 'button_url' => null]))
            ->call('create')->assertHasFormErrors(['button_url' => 'required_with']);
    }

    public function test_administrator_can_update_a_banner(): void
    {
        $banner = Banner::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm(array_merge($this->validData(), ['title' => 'Banner Diperbarui']))
            ->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseHas('banners', ['id' => $banner->id, 'title' => 'Banner Diperbarui']);
    }

    public function test_title_search_works(): void
    {
        $this->assertSearchFinds('Judul Patriot', 'title');
    }

    public function test_subtitle_search_works(): void
    {
        $this->assertSearchFinds('Subjudul Patriot', 'subtitle');
    }

    public function test_button_label_search_works(): void
    {
        $this->assertSearchFinds('Tombol Patriot', 'button_label');
    }

    public function test_active_filter_works(): void
    {
        $active = Banner::factory()->create(['is_active' => true]);
        $inactive = Banner::factory()->create(['is_active' => false]);
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(ListBanners::class)->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$active])->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_resource_orders_by_sort_order_then_id(): void
    {
        $later = Banner::factory()->create(['sort_order' => 5]);
        $earlier = Banner::factory()->create(['sort_order' => 1]);
        $this->assertSame([$earlier->id, $later->id], BannerResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_banner_can_be_deleted_individually(): void
    {
        $banner = Banner::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])->callAction('delete');
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    public function test_deleting_banner_removes_image_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/delete_me.webp', 'dummy');

        $banner = Banner::factory()->create(['image_path' => 'banners/delete_me.webp']);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])->callAction('delete');

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing('banners/delete_me.webp');
    }

    public function test_banner_with_null_image_path_can_be_deleted(): void
    {
        Storage::fake('public');
        $banner = Banner::factory()->create(['image_path' => null]);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])->callAction('delete');

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    public function test_banner_with_missing_image_file_can_be_deleted(): void
    {
        Storage::fake('public');
        $banner = Banner::factory()->create(['image_path' => 'banners/missing.webp']);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])->callAction('delete');

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    public function test_valid_image_can_be_uploaded_and_path_saved(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('banner.jpg')->size(1024);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreateBanner::class)
            ->fillForm(array_merge($this->validData(), ['image_path' => $file]))
            ->call('create')
            ->assertHasNoFormErrors();

        $banner = Banner::query()->latest('id')->first();
        $this->assertNotNull($banner->image_path);
        $this->assertStringStartsWith('banners/', $banner->image_path);
        Storage::disk('public')->assertExists($banner->image_path);
    }

    public function test_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreateBanner::class)
            ->fillForm(array_merge($this->validData(), ['image_path' => $file]))
            ->call('create')
            ->assertHasFormErrors(['image_path']);
    }

    public function test_file_exceeding_2mb_is_rejected(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('large.jpg')->size(2500);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreateBanner::class)
            ->fillForm(array_merge($this->validData(), ['image_path' => $file]))
            ->call('create')
            ->assertHasFormErrors(['image_path']);
    }

    public function test_updating_banner_image_removes_old_image_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/old.webp', 'old content');
        Storage::disk('public')->put('banners/new.webp', 'new content');

        $banner = Banner::factory()->create(['image_path' => 'banners/old.webp']);

        // Assert old file exists before update
        Storage::disk('public')->assertExists('banners/old.webp');

        // Update image_path directly on the model instance (unit test for model lifecycle)
        $banner->image_path = 'banners/new.webp';
        $banner->save();

        // New path must differ from old path
        $this->assertNotSame('banners/old.webp', $banner->image_path);
        $this->assertSame('banners/new.webp', $banner->image_path);

        // New file must exist on disk
        Storage::disk('public')->assertExists('banners/new.webp');

        // Old file must be gone
        Storage::disk('public')->assertMissing('banners/old.webp');
    }

    public function test_replacing_image_via_edit_form_removes_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/old.webp', 'old content');
        $newFile = UploadedFile::fake()->image('new.jpg')->size(1024);

        $banner = Banner::factory()->create(['image_path' => 'banners/old.webp']);

        // Assert old file exists before form submission
        Storage::disk('public')->assertExists('banners/old.webp');

        $this->actingAs(User::factory()->admin()->create());
        $component = Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()]);

        $component->fillForm($this->validData());

        $state = $component->get('data.image_path');
        $this->assertIsArray($state);
        $this->assertContains('banners/old.webp', $state);

        $oldFileKey = array_search('banners/old.webp', $state, true);
        $this->assertNotFalse($oldFileKey);

        $component->call(
            'callSchemaComponentMethod',
            'form.image_path',
            'deleteUploadedFile',
            ['fileKey' => $oldFileKey],
        );

        $newState = $component->get('data.image_path');
        $this->assertNotContains('banners/old.webp', $newState);

        $component->set('data.image_path', [$newFile]);

        $component->call('save')->assertHasNoFormErrors();

        $banner->refresh();

        // image_path must have changed from the old path
        $this->assertNotSame('banners/old.webp', $banner->image_path);
        $this->assertStringStartsWith('banners/', $banner->image_path);

        // New file must exist on disk
        Storage::disk('public')->assertExists($banner->image_path);

        // Old file must be gone
        Storage::disk('public')->assertMissing('banners/old.webp');
    }

    public function test_updating_banner_image_succeeds_when_old_file_is_missing(): void
    {
        Storage::fake('public');
        $newFile = UploadedFile::fake()->image('new.jpg')->size(1024);

        $banner = Banner::factory()->create(['image_path' => 'banners/missing.webp']);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm(array_merge($this->validData(), ['image_path' => $newFile]))
            ->call('save')
            ->assertHasNoFormErrors();

        $banner->refresh();
        $this->assertStringStartsWith('banners/', $banner->image_path);
        Storage::disk('public')->assertExists($banner->image_path);
    }

    public function test_edit_without_new_upload_retains_image_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/existing.webp', 'dummy content');

        $banner = Banner::factory()->create(['image_path' => 'banners/existing.webp']);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->set('data.title', 'Judul Baru')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('banners/existing.webp', $banner->refresh()->image_path);
        Storage::disk('public')->assertExists('banners/existing.webp');
    }

    public function test_consecutive_updates_do_not_leak_old_image_path_state(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/original.webp', 'dummy');

        $banner = Banner::factory()->create(['image_path' => 'banners/original.webp']);

        // Update 1: Change image directly on the instance
        Storage::disk('public')->put('banners/replacement.webp', 'dummy new');
        $banner->image_path = 'banners/replacement.webp';
        $banner->save();

        Storage::disk('public')->assertMissing('banners/original.webp');
        Storage::disk('public')->assertExists('banners/replacement.webp');

        // Recreate original file to simulate it being used by something else or re-uploaded manually
        Storage::disk('public')->put('banners/original.webp', 'dummy');

        // Update 2: Update field OTHER THAN image_path on the SAME model instance
        $banner->title = 'Judul Baru Tanpa Ubah Gambar';
        $banner->save();

        // If oldImagePath leaked, it would delete 'banners/original.webp' here
        Storage::disk('public')->assertExists('banners/original.webp');
        $this->assertSame('banners/replacement.webp', $banner->image_path);
    }

    public function test_banner_table_has_no_bulk_actions(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(ListBanners::class)->assertCountTableRecords(0)->assertActionDoesNotExist('deleteBulk');
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

    private function assertCreateError(string $field, mixed $value, ?string $rule = null): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreateBanner::class)
            ->fillForm(array_merge($this->validData(), [$field => $value]))
            ->call('create')->assertHasFormErrors([$field => $rule]);
    }

    private function assertButtonUrlAccepted(string $url): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(CreateBanner::class)
            ->fillForm(array_merge($this->validData(), ['button_url' => $url]))
            ->call('create')->assertHasNoFormErrors();
    }

    private function assertSearchFinds(string $needle, string $field): void
    {
        $wanted = Banner::factory()->create([$field => $needle]);
        $hidden = Banner::factory()->create([$field => 'Konten lain']);
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(ListBanners::class)->searchTable($needle)
            ->assertCanSeeTableRecords([$wanted])->assertCanNotSeeTableRecords([$hidden]);
    }

    /** @return array<string, mixed> */
    private function validData(): array
    {
        return [
            'title' => 'Banner Uji',
            'subtitle' => 'Banner fiktif untuk pengujian.',
            'button_label' => 'Lihat Produk',
            'button_url' => '/produk',
            'is_active' => true,
            'sort_order' => 4,
        ];
    }
}
