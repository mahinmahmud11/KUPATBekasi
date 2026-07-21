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

    public function test_image_path_is_not_changed_through_the_form(): void
    {
        $banner = Banner::factory()->create(['image_path' => 'banners/existing.webp']);
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm($this->validData())->call('save')->assertHasNoFormErrors();
        $this->assertSame('banners/existing.webp', $banner->refresh()->image_path);
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
