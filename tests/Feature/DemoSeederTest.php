<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\KupatBekasiDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_complete_deterministic_dataset(): void
    {
        $this->seed(KupatBekasiDemoSeeder::class);

        $this->assertSame(6, Category::query()->count());
        $this->assertSame(10, Partner::query()->count());
        $this->assertSame(30, Product::query()->count());
        $this->assertGreaterThanOrEqual(2, Banner::query()->count());
        $this->assertSame(1, SiteSetting::query()->count());
        $this->assertSame(0, User::query()->count());

        Partner::query()->each(function (Partner $partner): void {
            $this->assertSame(3, $partner->products()->count());
        });

        $this->assertUniqueValues('categories', 'slug');
        $this->assertUniqueValues('partners', 'slug');
        $this->assertUniqueValues('products', 'slug');
        $this->assertUniqueValues('partners', 'whatsapp');
        $this->assertSame(0, Partner::query()->where('whatsapp', 'not like', '628000%')->count());

        Product::query()->with(['partner', 'category'])->each(function (Product $product): void {
            $this->assertNotNull($product->partner);
            $this->assertNotNull($product->category);
        });

        $this->assertNoExternalMediaUrls();
    }

    public function test_demo_seeder_can_run_twice_without_adding_records(): void
    {
        $this->seed(KupatBekasiDemoSeeder::class);
        $firstCounts = $this->recordCounts();

        $this->seed(KupatBekasiDemoSeeder::class);

        $this->assertSame($firstCounts, $this->recordCounts());
    }

    public function test_demo_seeder_restores_soft_deleted_partners_and_products(): void
    {
        $this->seed(KupatBekasiDemoSeeder::class);
        Partner::query()->where('slug', 'dapur-patriot-rasa')->firstOrFail()->delete();
        Product::query()->where('slug', 'keripik-singkong-rempah')->firstOrFail()->delete();

        $this->seed(KupatBekasiDemoSeeder::class);

        $this->assertSame(10, Partner::query()->count());
        $this->assertSame(30, Product::query()->count());
        $this->assertSame(0, Partner::onlyTrashed()->count());
        $this->assertSame(0, Product::onlyTrashed()->count());
    }

    private function assertUniqueValues(string $table, string $column): void
    {
        $total = DB::table($table)->count();
        $unique = DB::table($table)->distinct()->count($column);

        $this->assertSame($total, $unique);
    }

    private function recordCounts(): array
    {
        return [
            'categories' => Category::query()->count(),
            'partners' => Partner::query()->count(),
            'products' => Product::query()->count(),
            'banners' => Banner::query()->count(),
            'site_settings' => SiteSetting::query()->count(),
            'users' => User::query()->count(),
        ];
    }

    private function assertNoExternalMediaUrls(): void
    {
        $mediaColumns = [
            'partners' => ['logo_path', 'cover_path'],
            'products' => ['main_image_path'],
            'banners' => ['image_path'],
            'site_settings' => ['logo_path', 'favicon_path'],
        ];

        foreach ($mediaColumns as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertSame(
                    0,
                    DB::table($table)->where($column, 'like', 'http%')->count(),
                    "{$table}.{$column} contains an external URL.",
                );
            }
        }
    }
}
