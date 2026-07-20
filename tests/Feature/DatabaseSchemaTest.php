<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_tables_have_their_important_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('partners', [
            'id', 'name', 'slug', 'whatsapp', 'is_featured', 'is_active', 'sort_order', 'deleted_at',
        ]));
        $this->assertTrue(Schema::hasColumns('categories', [
            'id', 'name', 'slug', 'is_active', 'sort_order',
        ]));
        $this->assertTrue(Schema::hasColumns('products', [
            'id', 'partner_id', 'category_id', 'name', 'slug', 'description', 'price', 'stock_status',
            'is_featured', 'is_active', 'sort_order', 'deleted_at',
        ]));
        $this->assertTrue(Schema::hasColumns('product_images', [
            'id', 'product_id', 'image_path', 'alt_text', 'sort_order',
        ]));
        $this->assertTrue(Schema::hasColumns('banners', [
            'id', 'title', 'subtitle', 'image_path', 'is_active', 'sort_order',
        ]));
        $this->assertTrue(Schema::hasColumns('site_settings', [
            'id', 'site_name', 'contact_whatsapp', 'contact_email', 'logo_path', 'favicon_path',
        ]));
    }

    public function test_product_foreign_keys_restrict_parent_deletion(): void
    {
        $partnerId = DB::table('partners')->insertGetId([
            'name' => 'Mitra Uji',
            'slug' => 'mitra-uji',
            'whatsapp' => '628123456789',
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji',
        ]);

        DB::table('products')->insert([
            'partner_id' => $partnerId,
            'category_id' => $categoryId,
            'name' => 'Produk Uji',
            'slug' => 'produk-uji',
            'price' => 10000,
        ]);

        try {
            DB::table('partners')->where('id', $partnerId)->delete();
            $this->fail('Partner yang masih memiliki produk seharusnya tidak dapat dihapus.');
        } catch (QueryException) {
            $this->assertDatabaseHas('partners', ['id' => $partnerId]);
        }

        $this->expectException(QueryException::class);
        DB::table('categories')->where('id', $categoryId)->delete();
    }

    public function test_deleting_a_product_cascades_its_images(): void
    {
        $partnerId = DB::table('partners')->insertGetId([
            'name' => 'Mitra Galeri',
            'slug' => 'mitra-galeri',
            'whatsapp' => '628123456780',
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Kategori Galeri',
            'slug' => 'kategori-galeri',
        ]);
        $productId = DB::table('products')->insertGetId([
            'partner_id' => $partnerId,
            'category_id' => $categoryId,
            'name' => 'Produk Galeri',
            'slug' => 'produk-galeri',
            'price' => 20000,
        ]);

        DB::table('product_images')->insert([
            'product_id' => $productId,
            'image_path' => 'products/example.webp',
        ]);

        DB::table('products')->where('id', $productId)->delete();

        $this->assertDatabaseMissing('product_images', ['product_id' => $productId]);
    }
}
