<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_create_valid_domain_models(): void
    {
        $partner = Partner::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $productImage = ProductImage::factory()->create();
        $banner = Banner::factory()->create();
        $siteSetting = SiteSetting::factory()->create();

        $this->assertDatabaseHas('partners', ['id' => $partner->id]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('product_images', ['id' => $productImage->id]);
        $this->assertDatabaseHas('banners', ['id' => $banner->id]);
        $this->assertDatabaseHas('site_settings', ['id' => $siteSetting->id]);
        $this->assertNotNull($product->partner_id);
        $this->assertNotNull($product->category_id);
        $this->assertIsInt($product->price);
        $this->assertStringStartsWith('628000', $partner->whatsapp);
    }

    public function test_main_relationships_are_connected(): void
    {
        $partner = Partner::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()
            ->for($partner)
            ->for($category)
            ->create();
        $image = ProductImage::factory()->for($product)->create();

        $this->assertTrue($partner->products->contains($product));
        $this->assertTrue($category->products->contains($product));
        $this->assertTrue($product->partner->is($partner));
        $this->assertTrue($product->category->is($category));
        $this->assertTrue($product->images->contains($image));
        $this->assertTrue($image->product->is($product));
    }

    public function test_active_featured_and_ordered_scopes_filter_and_sort_records(): void
    {
        $partnerLater = Partner::factory()->create([
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 20,
        ]);
        $partnerEarlier = Partner::factory()->create([
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 10,
        ]);
        $inactivePartner = Partner::factory()->create([
            'is_active' => false,
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        $this->assertEqualsCanonicalizing(
            [$partnerLater->id, $partnerEarlier->id],
            Partner::active()->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$partnerLater->id, $inactivePartner->id],
            Partner::featured()->pluck('id')->all(),
        );
        $this->assertSame(
            [$inactivePartner->id, $partnerEarlier->id, $partnerLater->id],
            Partner::ordered()->pluck('id')->all(),
        );

        $categoryLater = Category::factory()->create(['is_active' => true, 'sort_order' => 20]);
        $categoryEarlier = Category::factory()->create(['is_active' => true, 'sort_order' => 10]);
        $inactiveCategory = Category::factory()->create(['is_active' => false, 'sort_order' => 0]);

        $this->assertEqualsCanonicalizing(
            [$categoryLater->id, $categoryEarlier->id],
            Category::active()->pluck('id')->all(),
        );
        $this->assertSame(
            [$inactiveCategory->id, $categoryEarlier->id, $categoryLater->id],
            Category::ordered()->pluck('id')->all(),
        );

        $productLater = Product::factory()->create([
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 20,
        ]);
        $productEarlier = Product::factory()->create([
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 10,
        ]);
        $inactiveProduct = Product::factory()->create([
            'is_active' => false,
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        $this->assertEqualsCanonicalizing(
            [$productLater->id, $productEarlier->id],
            Product::active()->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$productLater->id, $inactiveProduct->id],
            Product::featured()->pluck('id')->all(),
        );
        $this->assertSame(
            [$inactiveProduct->id, $productEarlier->id, $productLater->id],
            Product::ordered()->pluck('id')->all(),
        );

        $imageLater = ProductImage::factory()->create(['sort_order' => 20]);
        $imageEarlier = ProductImage::factory()->create(['sort_order' => 10]);

        $this->assertSame(
            [$imageEarlier->id, $imageLater->id],
            ProductImage::ordered()->pluck('id')->all(),
        );

        $bannerLater = Banner::factory()->create(['is_active' => true, 'sort_order' => 20]);
        $bannerEarlier = Banner::factory()->create(['is_active' => true, 'sort_order' => 10]);
        $inactiveBanner = Banner::factory()->create(['is_active' => false, 'sort_order' => 0]);

        $this->assertEqualsCanonicalizing(
            [$bannerLater->id, $bannerEarlier->id],
            Banner::active()->pluck('id')->all(),
        );
        $this->assertSame(
            [$inactiveBanner->id, $bannerEarlier->id, $bannerLater->id],
            Banner::ordered()->pluck('id')->all(),
        );
    }

    public function test_boolean_and_integer_attributes_are_cast(): void
    {
        $partner = Partner::factory()->create([
            'is_active' => 1,
            'is_featured' => 0,
            'sort_order' => '7',
        ])->refresh();
        $product = Product::factory()->create([
            'price' => '125000',
            'is_active' => 1,
            'is_featured' => 0,
            'sort_order' => '9',
        ])->refresh();
        $category = Category::factory()->create(['is_active' => 1, 'sort_order' => '3'])->refresh();
        $image = ProductImage::factory()->create(['sort_order' => '4'])->refresh();
        $banner = Banner::factory()->create(['is_active' => 1, 'sort_order' => '5'])->refresh();

        $this->assertIsBool($partner->is_active);
        $this->assertIsBool($partner->is_featured);
        $this->assertIsInt($partner->sort_order);
        $this->assertIsInt($product->price);
        $this->assertIsBool($product->is_active);
        $this->assertIsBool($product->is_featured);
        $this->assertIsInt($product->sort_order);
        $this->assertIsBool($category->is_active);
        $this->assertIsInt($category->sort_order);
        $this->assertIsInt($image->sort_order);
        $this->assertIsBool($banner->is_active);
        $this->assertIsInt($banner->sort_order);
    }

    public function test_partner_and_product_use_soft_deletes(): void
    {
        $partner = Partner::factory()->create();
        $product = Product::factory()->create();

        $partner->delete();
        $product->delete();

        $this->assertSoftDeleted($partner);
        $this->assertSoftDeleted($product);
        $this->assertNull(Partner::find($partner->id));
        $this->assertNull(Product::find($product->id));
        $this->assertNotNull(Partner::withTrashed()->find($partner->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }
}
