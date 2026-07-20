<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class KupatBekasiDemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Makanan', 'slug' => 'makanan', 'description' => 'Aneka makanan olahan UMKM Kota Bekasi.', 'icon' => null, 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Minuman', 'slug' => 'minuman', 'description' => 'Minuman segar dan produk minuman kemasan.', 'icon' => null, 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Fesyen', 'slug' => 'fesyen', 'description' => 'Busana dan aksesori karya pelaku usaha lokal.', 'icon' => null, 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Kerajinan', 'slug' => 'kerajinan', 'description' => 'Produk kerajinan kreatif dan dekorasi.', 'icon' => null, 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Kecantikan', 'slug' => 'kecantikan', 'description' => 'Produk perawatan diri buatan UMKM.', 'icon' => null, 'is_active' => true, 'sort_order' => 5],
            ['name' => 'Jasa dan Lainnya', 'slug' => 'jasa-dan-lainnya', 'description' => 'Layanan dan produk kreatif lainnya.', 'icon' => null, 'is_active' => true, 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], $category);
        }

        $partners = [
            ['name' => 'Dapur Patriot Rasa', 'slug' => 'dapur-patriot-rasa', 'district' => 'Bekasi Timur', 'whatsapp' => '628000000001', 'is_featured' => true],
            ['name' => 'Kedai Embun Bekasi', 'slug' => 'kedai-embun-bekasi', 'district' => 'Bekasi Selatan', 'whatsapp' => '628000000002', 'is_featured' => true],
            ['name' => 'Laras Busana Nusa', 'slug' => 'laras-busana-nusa', 'district' => 'Rawalumbu', 'whatsapp' => '628000000003', 'is_featured' => false],
            ['name' => 'Kriya Bambu Harapan', 'slug' => 'kriya-bambu-harapan', 'district' => 'Bantargebang', 'whatsapp' => '628000000004', 'is_featured' => true],
            ['name' => 'Rona Cantik Alami', 'slug' => 'rona-cantik-alami', 'district' => 'Pondok Gede', 'whatsapp' => '628000000005', 'is_featured' => false],
            ['name' => 'Studio Kreasi Jatiasih', 'slug' => 'studio-kreasi-jatiasih', 'district' => 'Jatiasih', 'whatsapp' => '628000000006', 'is_featured' => false],
            ['name' => 'Pangan Sejahtera Medan Satria', 'slug' => 'pangan-sejahtera-medan-satria', 'district' => 'Medan Satria', 'whatsapp' => '628000000007', 'is_featured' => true],
            ['name' => 'Serambi Minuman Segar', 'slug' => 'serambi-minuman-segar', 'district' => 'Bekasi Utara', 'whatsapp' => '628000000008', 'is_featured' => false],
            ['name' => 'Tenun Ceria Mustika', 'slug' => 'tenun-ceria-mustika', 'district' => 'Mustika Jaya', 'whatsapp' => '628000000009', 'is_featured' => false],
            ['name' => 'Rumah Karya Pondok Melati', 'slug' => 'rumah-karya-pondok-melati', 'district' => 'Pondok Melati', 'whatsapp' => '628000000010', 'is_featured' => true],
        ];

        $products = [
            'dapur-patriot-rasa' => [
                ['name' => 'Keripik Singkong Rempah', 'slug' => 'keripik-singkong-rempah', 'category' => 'makanan', 'price' => 18000, 'unit' => 'bungkus', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Sambal Bawang Patriot', 'slug' => 'sambal-bawang-patriot', 'category' => 'makanan', 'price' => 28000, 'unit' => 'botol', 'stock_status' => 'available', 'is_featured' => false],
                ['name' => 'Kue Kering Wijen', 'slug' => 'kue-kering-wijen', 'category' => 'makanan', 'price' => 35000, 'unit' => 'toples', 'stock_status' => 'preorder', 'is_featured' => false],
            ],
            'kedai-embun-bekasi' => [
                ['name' => 'Sirup Rosela Embun', 'slug' => 'sirup-rosela-embun', 'category' => 'minuman', 'price' => 32000, 'unit' => 'botol', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Kopi Susu Aren Literan', 'slug' => 'kopi-susu-aren-literan', 'category' => 'minuman', 'price' => 65000, 'unit' => 'liter', 'stock_status' => 'preorder', 'is_featured' => false],
                ['name' => 'Teh Rempah Sejuk', 'slug' => 'teh-rempah-sejuk', 'category' => 'minuman', 'price' => 22000, 'unit' => 'botol', 'stock_status' => 'available', 'is_featured' => false],
            ],
            'laras-busana-nusa' => [
                ['name' => 'Tunik Laras Harian', 'slug' => 'tunik-laras-harian', 'category' => 'fesyen', 'price' => 175000, 'unit' => 'buah', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Pashmina Warna Bumi', 'slug' => 'pashmina-warna-bumi', 'category' => 'fesyen', 'price' => 55000, 'unit' => 'buah', 'stock_status' => 'available', 'is_featured' => false],
                ['name' => 'Tas Kain Serbaguna', 'slug' => 'tas-kain-serbaguna', 'category' => 'fesyen', 'price' => 75000, 'unit' => 'buah', 'stock_status' => 'unavailable', 'is_featured' => false],
            ],
            'kriya-bambu-harapan' => [
                ['name' => 'Bakul Bambu Modern', 'slug' => 'bakul-bambu-modern', 'category' => 'kerajinan', 'price' => 85000, 'unit' => 'buah', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Lampu Meja Anyaman', 'slug' => 'lampu-meja-anyaman', 'category' => 'kerajinan', 'price' => 145000, 'unit' => 'buah', 'stock_status' => 'preorder', 'is_featured' => false],
                ['name' => 'Wadah Alat Tulis Bambu', 'slug' => 'wadah-alat-tulis-bambu', 'category' => 'kerajinan', 'price' => 45000, 'unit' => 'buah', 'stock_status' => 'available', 'is_featured' => false],
            ],
            'rona-cantik-alami' => [
                ['name' => 'Sabun Mandi Serai', 'slug' => 'sabun-mandi-serai', 'category' => 'kecantikan', 'price' => 25000, 'unit' => 'batang', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Balm Bibir Madu', 'slug' => 'balm-bibir-madu', 'category' => 'kecantikan', 'price' => 30000, 'unit' => 'pot', 'stock_status' => 'available', 'is_featured' => false],
                ['name' => 'Lulur Rempah Tradisi', 'slug' => 'lulur-rempah-tradisi', 'category' => 'kecantikan', 'price' => 48000, 'unit' => 'jar', 'stock_status' => 'preorder', 'is_featured' => false],
            ],
            'studio-kreasi-jatiasih' => [
                ['name' => 'Paket Desain Undangan Digital', 'slug' => 'paket-desain-undangan-digital', 'category' => 'jasa-dan-lainnya', 'price' => 150000, 'unit' => 'paket', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Cetak Stiker Usaha', 'slug' => 'cetak-stiker-usaha', 'category' => 'jasa-dan-lainnya', 'price' => 45000, 'unit' => 'paket', 'stock_status' => 'available', 'is_featured' => false],
                ['name' => 'Foto Produk Sederhana', 'slug' => 'foto-produk-sederhana', 'category' => 'jasa-dan-lainnya', 'price' => 200000, 'unit' => 'paket', 'stock_status' => 'preorder', 'is_featured' => false],
            ],
            'pangan-sejahtera-medan-satria' => [
                ['name' => 'Abon Jamur Gurih', 'slug' => 'abon-jamur-gurih', 'category' => 'makanan', 'price' => 42000, 'unit' => 'toples', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Bawang Goreng Renyah', 'slug' => 'bawang-goreng-renyah', 'category' => 'makanan', 'price' => 38000, 'unit' => 'toples', 'stock_status' => 'available', 'is_featured' => false],
                ['name' => 'Kacang Bumbu Daun Jeruk', 'slug' => 'kacang-bumbu-daun-jeruk', 'category' => 'makanan', 'price' => 26000, 'unit' => 'bungkus', 'stock_status' => 'available', 'is_featured' => false],
            ],
            'serambi-minuman-segar' => [
                ['name' => 'Sari Lemon Madu', 'slug' => 'sari-lemon-madu', 'category' => 'minuman', 'price' => 27000, 'unit' => 'botol', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Jahe Merah Instan', 'slug' => 'jahe-merah-instan', 'category' => 'minuman', 'price' => 40000, 'unit' => 'pouch', 'stock_status' => 'available', 'is_featured' => false],
                ['name' => 'Kunyit Asam Segar', 'slug' => 'kunyit-asam-segar', 'category' => 'minuman', 'price' => 18000, 'unit' => 'botol', 'stock_status' => 'unavailable', 'is_featured' => false],
            ],
            'tenun-ceria-mustika' => [
                ['name' => 'Outer Motif Geometris', 'slug' => 'outer-motif-geometris', 'category' => 'fesyen', 'price' => 210000, 'unit' => 'buah', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Dompet Kain Etnik', 'slug' => 'dompet-kain-etnik', 'category' => 'fesyen', 'price' => 65000, 'unit' => 'buah', 'stock_status' => 'available', 'is_featured' => false],
                ['name' => 'Syal Tenun Ceria', 'slug' => 'syal-tenun-ceria', 'category' => 'fesyen', 'price' => 95000, 'unit' => 'buah', 'stock_status' => 'preorder', 'is_featured' => false],
            ],
            'rumah-karya-pondok-melati' => [
                ['name' => 'Hiasan Dinding Makrame', 'slug' => 'hiasan-dinding-makrame', 'category' => 'kerajinan', 'price' => 125000, 'unit' => 'buah', 'stock_status' => 'available', 'is_featured' => true],
                ['name' => 'Pot Kain Daur Ulang', 'slug' => 'pot-kain-daur-ulang', 'category' => 'kerajinan', 'price' => 60000, 'unit' => 'buah', 'stock_status' => 'available', 'is_featured' => false],
                ['name' => 'Gantungan Kunci Rajut', 'slug' => 'gantungan-kunci-rajut', 'category' => 'kerajinan', 'price' => 20000, 'unit' => 'buah', 'stock_status' => 'available', 'is_featured' => false],
            ],
        ];

        foreach ($partners as $index => $partnerData) {
            $partner = Partner::withTrashed()->updateOrCreate(
                ['slug' => $partnerData['slug']],
                array_merge($partnerData, [
                    'owner_name' => null,
                    'short_description' => 'UMKM dummy untuk demonstrasi katalog KUPATBekasi.',
                    'description' => "{$partnerData['name']} adalah profil usaha fiktif untuk kebutuhan demonstrasi.",
                    'address' => "Kawasan {$partnerData['district']}, Kota Bekasi",
                    'instagram_url' => null,
                    'logo_path' => null,
                    'cover_path' => null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]),
            );
            $partner->restore();

            foreach ($products[$partnerData['slug']] as $productIndex => $productData) {
                $category = Category::query()->where('slug', $productData['category'])->firstOrFail();
                unset($productData['category']);

                $product = Product::withTrashed()->updateOrCreate(
                    ['slug' => $productData['slug']],
                    array_merge($productData, [
                        'partner_id' => $partner->id,
                        'category_id' => $category->id,
                        'short_description' => 'Produk dummy pilihan untuk demonstrasi katalog KUPATBekasi.',
                        'description' => 'Data produk fiktif yang disiapkan khusus untuk kebutuhan demo dan screenshot.',
                        'main_image_path' => null,
                        'is_active' => true,
                        'sort_order' => $productIndex + 1,
                    ]),
                );
                $product->restore();
            }
        }

        $banners = [
            ['title' => 'Bangga Produk UMKM Kota Bekasi', 'subtitle' => 'Temukan produk pilihan dari pelaku usaha lokal dalam katalog demo KUPATBekasi.', 'image_path' => null, 'button_label' => 'Lihat Produk', 'button_url' => '/produk', 'is_active' => true, 'sort_order' => 1],
            ['title' => 'Jelajahi Karya Mitra Lokal', 'subtitle' => 'Kenali ragam produk dan layanan kreatif UMKM di berbagai wilayah Kota Bekasi.', 'image_path' => null, 'button_label' => 'Lihat Mitra', 'button_url' => '/mitra', 'is_active' => true, 'sort_order' => 2],
        ];

        foreach ($banners as $banner) {
            Banner::query()->updateOrCreate(['sort_order' => $banner['sort_order']], $banner);
        }

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'KUPATBekasi',
                'tagline' => 'Katalog UMKM Patriot Binaan Kota Bekasi',
                'about_summary' => 'Data demonstrasi katalog digital untuk memperkenalkan produk UMKM lokal Kota Bekasi.',
                'contact_whatsapp' => '628000000000',
                'contact_email' => 'demo@kupatbekasi.test',
                'address' => 'Kota Bekasi, Jawa Barat',
                'instagram_url' => null,
                'logo_path' => null,
                'favicon_path' => null,
            ],
        );
    }
}
