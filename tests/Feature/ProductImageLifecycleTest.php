<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_image_path_removes_old_file_and_keeps_new_one(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/gallery/old.webp', 'old content');
        Storage::disk('public')->put('products/gallery/new.webp', 'new content');

        $product = Product::factory()->create();
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/old.webp',
        ]);

        $image->image_path = 'products/gallery/new.webp';
        $image->save();

        Storage::disk('public')->assertMissing('products/gallery/old.webp');
        Storage::disk('public')->assertExists('products/gallery/new.webp');
    }

    public function test_changing_alt_text_and_sort_order_does_not_remove_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/gallery/keep.webp', 'content');

        $product = Product::factory()->create();
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/keep.webp',
            'alt_text' => 'old alt',
            'sort_order' => 1,
        ]);

        $image->alt_text = 'new alt';
        $image->sort_order = 2;
        $image->save();

        Storage::disk('public')->assertExists('products/gallery/keep.webp');
    }

    public function test_deleting_product_image_removes_physical_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/gallery/delete.webp', 'content');

        $product = Product::factory()->create();
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/delete.webp',
        ]);

        $image->delete();

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing('products/gallery/delete.webp');
    }

    public function test_soft_deleting_product_retains_main_image_gallery_files_and_records(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/main/main.webp', 'main');
        Storage::disk('public')->put('products/gallery/g1.webp', 'g1');
        Storage::disk('public')->put('products/gallery/g2.webp', 'g2');

        $product = Product::factory()->create([
            'main_image_path' => 'products/main/main.webp',
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/g1.webp',
        ]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/g2.webp',
        ]);

        $product->delete(); // Soft delete

        $this->assertSoftDeleted($product);
        $this->assertDatabaseHas('product_images', ['product_id' => $product->id]);

        Storage::disk('public')->assertExists('products/main/main.webp');
        Storage::disk('public')->assertExists('products/gallery/g1.webp');
        Storage::disk('public')->assertExists('products/gallery/g2.webp');
    }

    public function test_force_deleting_product_removes_main_image_gallery_files_and_records(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/main/main.webp', 'main');
        Storage::disk('public')->put('products/gallery/g1.webp', 'g1');
        Storage::disk('public')->put('products/gallery/g2.webp', 'g2');

        $product = Product::factory()->create([
            'main_image_path' => 'products/main/main.webp',
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/g1.webp',
        ]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/g2.webp',
        ]);

        $product->forceDelete();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);

        Storage::disk('public')->assertMissing('products/main/main.webp');
        Storage::disk('public')->assertMissing('products/gallery/g1.webp');
        Storage::disk('public')->assertMissing('products/gallery/g2.webp');
    }

    public function test_force_deleting_is_safe_when_main_image_null_gallery_empty_or_file_missing(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/gallery/g1.webp', 'g1');

        $product = Product::factory()->create([
            'main_image_path' => null,
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/missing.webp',
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/g1.webp',
        ]);

        $product2 = Product::factory()->create([
            'main_image_path' => null,
        ]);

        $product->forceDelete();
        $product2->forceDelete();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('products', ['id' => $product2->id]);
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);
        Storage::disk('public')->assertMissing('products/gallery/g1.webp');
    }

    public function test_consecutive_updates_on_product_image_do_not_leak_state(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/gallery/original.webp', 'original content');
        Storage::disk('public')->put('products/gallery/replacement.webp', 'replacement content');

        $product = Product::factory()->create();
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/original.webp',
        ]);

        $image->image_path = 'products/gallery/replacement.webp';
        $image->save();

        Storage::disk('public')->assertMissing('products/gallery/original.webp');
        Storage::disk('public')->assertExists('products/gallery/replacement.webp');

        Storage::disk('public')->put('products/gallery/original.webp', 'leaked original content');

        $image->alt_text = 'some alt';
        $image->save();

        Storage::disk('public')->assertExists('products/gallery/original.webp');
        Storage::disk('public')->assertExists('products/gallery/replacement.webp');
        $this->assertSame('products/gallery/replacement.webp', $image->image_path);
    }

    public function test_force_deleting_already_soft_deleted_product_removes_all_media_and_records(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/main/trashed-main.webp', 'content');
        Storage::disk('public')->put('products/gallery/trashed-g1.webp', 'content');
        Storage::disk('public')->put('products/gallery/trashed-g2.webp', 'content');

        $product = Product::factory()->create([
            'main_image_path' => 'products/main/trashed-main.webp',
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/trashed-g1.webp',
        ]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery/trashed-g2.webp',
        ]);

        $product->delete();

        $this->assertSoftDeleted($product);
        // We know we have 2 product images created
        $this->assertSame(2, ProductImage::where('product_id', $product->id)->count());
        Storage::disk('public')->assertExists('products/main/trashed-main.webp');
        Storage::disk('public')->assertExists('products/gallery/trashed-g1.webp');
        Storage::disk('public')->assertExists('products/gallery/trashed-g2.webp');

        $trashedProduct = Product::withTrashed()->findOrFail($product->id);
        $trashedProduct->forceDelete();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);
        Storage::disk('public')->assertMissing('products/main/trashed-main.webp');
        Storage::disk('public')->assertMissing('products/gallery/trashed-g1.webp');
        Storage::disk('public')->assertMissing('products/gallery/trashed-g2.webp');
    }
}
