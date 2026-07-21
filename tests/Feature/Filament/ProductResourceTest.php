<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_the_product_list(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(ProductResource::getUrl('index'))
            ->assertOk();
    }

    public function test_regular_user_cannot_open_the_product_resource(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(ProductResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_administrator_can_create_a_product_with_relations(): void
    {
        $partner = Partner::factory()->create();
        $category = Category::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateProduct::class)
            ->fillForm($this->validProductData($partner, $category))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'partner_id' => $partner->id,
            'category_id' => $category->id,
            'name' => 'Produk Uji Bekasi',
            'slug' => 'produk-uji-bekasi',
            'price' => 25000,
            'stock_status' => 'available',
        ]);
    }

    public function test_slug_is_generated_from_name_when_slug_is_empty(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateProduct::class)
            ->set('data.slug', '')
            ->set('data.name', 'Keripik Patriot Baru')
            ->assertSet('data.slug', 'keripik-patriot-baru');
    }

    public function test_product_name_is_required(): void
    {
        $this->assertCreateFieldHasError('name', null, 'required');
    }

    public function test_partner_is_required(): void
    {
        $this->assertCreateFieldHasError('partner_id', null, 'required');
    }

    public function test_category_is_required(): void
    {
        $this->assertCreateFieldHasError('category_id', null, 'required');
    }

    public function test_product_slug_must_be_unique(): void
    {
        Product::factory()->create(['slug' => 'produk-uji-bekasi']);
        $this->assertCreateFieldHasError('slug', 'produk-uji-bekasi', 'unique');
    }

    public function test_price_is_required(): void
    {
        $this->assertCreateFieldHasError('price', null, 'required');
    }

    public function test_price_must_be_an_integer(): void
    {
        $this->assertCreateFieldHasError('price', 25000.50, 'integer');
    }

    public function test_price_cannot_be_negative(): void
    {
        $this->assertCreateFieldHasError('price', -1, 'min');
    }

    public function test_unit_is_required(): void
    {
        $this->assertCreateFieldHasError('unit', null, 'required');
    }

    public function test_invalid_stock_status_is_rejected(): void
    {
        $this->assertCreateFieldHasError('stock_status', 'sold-out');
    }

    public function test_administrator_can_update_a_product(): void
    {
        $product = Product::factory()->create();
        $newPartner = Partner::factory()->create();
        $newCategory = Category::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(array_merge($this->validProductData($newPartner, $newCategory), [
                'name' => 'Produk Diperbarui',
                'slug' => 'produk-diperbarui',
                'stock_status' => 'preorder',
                'is_active' => false,
            ]))
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'partner_id' => $newPartner->id,
            'category_id' => $newCategory->id,
            'name' => 'Produk Diperbarui',
            'stock_status' => 'preorder',
            'is_active' => false,
        ]);
    }

    public function test_product_name_search_works(): void
    {
        $wanted = Product::factory()->create(['name' => 'Dodol Patriot Unik']);
        $hidden = Product::factory()->create(['name' => 'Kerajinan Bambu']);
        $this->assertSearchFindsOnly('Dodol Patriot', $wanted, $hidden);
    }

    public function test_product_slug_search_works(): void
    {
        $wanted = Product::factory()->create(['slug' => 'produk-khas-rawalumbu']);
        $hidden = Product::factory()->create(['slug' => 'produk-lain']);
        $this->assertSearchFindsOnly('produk-khas-rawalumbu', $wanted, $hidden);
    }

    public function test_partner_name_search_works(): void
    {
        $wanted = Product::factory()->create(['partner_id' => Partner::factory()->create(['name' => 'Mitra Patriot Khusus'])]);
        $hidden = Product::factory()->create(['partner_id' => Partner::factory()->create(['name' => 'Mitra Lain'])]);
        $this->assertSearchFindsOnly('Mitra Patriot Khusus', $wanted, $hidden);
    }

    public function test_category_name_search_works(): void
    {
        $wanted = Product::factory()->create(['category_id' => Category::factory()->create(['name' => 'Kategori Uji Khusus'])]);
        $hidden = Product::factory()->create(['category_id' => Category::factory()->create(['name' => 'Kategori Lain'])]);
        $this->assertSearchFindsOnly('Kategori Uji Khusus', $wanted, $hidden);
    }

    public function test_category_filter_works(): void
    {
        $category = Category::factory()->create();
        $wanted = Product::factory()->create(['category_id' => $category]);
        $hidden = Product::factory()->create();
        $this->assertFilterFindsOnly('category_id', $category->id, $wanted, $hidden);
    }

    public function test_partner_filter_works(): void
    {
        $partner = Partner::factory()->create();
        $wanted = Product::factory()->create(['partner_id' => $partner]);
        $hidden = Product::factory()->create();
        $this->assertFilterFindsOnly('partner_id', $partner->id, $wanted, $hidden);
    }

    public function test_stock_status_filter_works(): void
    {
        $wanted = Product::factory()->create(['stock_status' => 'preorder']);
        $hidden = Product::factory()->create(['stock_status' => 'available']);
        $this->assertFilterFindsOnly('stock_status', 'preorder', $wanted, $hidden);
    }

    public function test_active_filter_works(): void
    {
        $wanted = Product::factory()->create(['is_active' => true]);
        $hidden = Product::factory()->create(['is_active' => false]);
        $this->assertFilterFindsOnly('is_active', true, $wanted, $hidden);
    }

    public function test_featured_filter_works(): void
    {
        $wanted = Product::factory()->create(['is_featured' => true]);
        $hidden = Product::factory()->create(['is_featured' => false]);
        $this->assertFilterFindsOnly('is_featured', true, $wanted, $hidden);
    }

    public function test_product_can_be_soft_deleted_individually(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('delete');

        $this->assertSoftDeleted($product);
    }

    public function test_relation_names_and_price_are_displayed(): void
    {
        $partner = Partner::factory()->create(['name' => 'Mitra Tampilan']);
        $category = Category::factory()->create(['name' => 'Kategori Tampilan']);
        $product = Product::factory()->create([
            'partner_id' => $partner,
            'category_id' => $category,
            'price' => 125000,
        ]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListProducts::class)
            ->assertTableColumnStateSet('partner.name', 'Mitra Tampilan', $product)
            ->assertTableColumnStateSet('category.name', 'Kategori Tampilan', $product)
            ->assertTableColumnFormattedStateSet('price', 'Rp 125.000', $product);
    }

    public function test_resource_query_eager_loads_relations_and_orders_records(): void
    {
        $later = Product::factory()->create(['sort_order' => 5]);
        $earlier = Product::factory()->create(['sort_order' => 1]);

        $products = ProductResource::getEloquentQuery()->get();

        $this->assertSame([$earlier->id, $later->id], $products->pluck('id')->all());
        $this->assertTrue($products->every(fn (Product $product): bool => $product->relationLoaded('partner')));
        $this->assertTrue($products->every(fn (Product $product): bool => $product->relationLoaded('category')));
    }

    public function test_main_image_path_is_not_changed_through_the_form(): void
    {
        $product = Product::factory()->create(['main_image_path' => 'products/existing.webp']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(array_merge($this->validProductData($product->partner, $product->category), [
                'name' => 'Produk Tanpa Perubahan Gambar',
                'slug' => 'produk-tanpa-perubahan-gambar',
            ]))
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('products/existing.webp', $product->refresh()->main_image_path);
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

    private function assertCreateFieldHasError(string $field, mixed $value, ?string $rule = null): void
    {
        $partner = Partner::factory()->create();
        $category = Category::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateProduct::class)
            ->fillForm(array_merge($this->validProductData($partner, $category), [$field => $value]))
            ->call('create')
            ->assertHasFormErrors([$field => $rule]);
    }

    private function assertSearchFindsOnly(string $search, Product $wanted, Product $hidden): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListProducts::class)
            ->searchTable($search)
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    private function assertFilterFindsOnly(string $filter, mixed $value, Product $wanted, Product $hidden): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListProducts::class)
            ->filterTable($filter, $value)
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validProductData(Partner $partner, Category $category): array
    {
        return [
            'partner_id' => $partner->id,
            'category_id' => $category->id,
            'name' => 'Produk Uji Bekasi',
            'slug' => 'produk-uji-bekasi',
            'short_description' => 'Produk fiktif untuk pengujian resource.',
            'description' => 'Deskripsi lengkap produk fiktif untuk kebutuhan pengujian.',
            'price' => 25000,
            'unit' => 'pak',
            'stock_status' => 'available',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 4,
        ];
    }
}
