<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_the_category_list(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(CategoryResource::getUrl('index'))
            ->assertOk();
    }

    public function test_regular_user_cannot_open_the_category_resource(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(CategoryResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_administrator_can_create_a_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Produk Digital',
                'slug' => 'produk-digital',
                'description' => 'Kategori layanan dan produk digital.',
                'icon' => 'heroicon-o-computer-desktop',
                'is_active' => true,
                'sort_order' => 7,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Produk Digital',
            'slug' => 'produk-digital',
            'is_active' => true,
            'sort_order' => 7,
        ]);
    }

    public function test_slug_is_generated_from_name_when_slug_is_empty(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateCategory::class)
            ->set('data.slug', '')
            ->set('data.name', 'Olahan Bekasi')
            ->assertSet('data.slug', 'olahan-bekasi');
    }

    public function test_category_name_is_required(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => null,
                'slug' => 'tanpa-nama',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_category_slug_must_be_unique(): void
    {
        Category::factory()->create(['slug' => 'makanan']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Makanan Lain',
                'slug' => 'makanan',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_administrator_can_update_a_category(): void
    {
        $category = Category::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name' => 'Kategori Diperbarui',
                'slug' => 'kategori-diperbarui',
                'is_active' => false,
                'sort_order' => 9,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Kategori Diperbarui',
            'slug' => 'kategori-diperbarui',
            'is_active' => false,
            'sort_order' => 9,
        ]);
    }

    public function test_category_search_works(): void
    {
        $wanted = Category::factory()->create(['name' => 'Kerajinan Pilihan']);
        $hidden = Category::factory()->create(['name' => 'Minuman Segar']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListCategories::class)
            ->searchTable('Kerajinan')
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_active_status_filter_works(): void
    {
        $active = Category::factory()->create(['is_active' => true]);
        $inactive = Category::factory()->create(['is_active' => false]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListCategories::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'partner_id' => Partner::factory(),
        ]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertActionHidden('delete');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_product_count_is_displayed_without_error(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'partner_id' => Partner::factory(),
        ]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$category])
            ->assertTableColumnStateSet('products_count', 2, $category);
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
}
