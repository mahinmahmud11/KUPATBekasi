<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class PurgeDemoData extends Command
{
    protected $signature = 'kupat:purge-demo-data {--force : Lewati konfirmasi penghapusan}';

    protected $description = 'Hapus data demo deterministik KUPATBekasi tanpa menyentuh data pengguna';

    private const PRODUCT_SLUGS = [
        'keripik-singkong-rempah',
        'sambal-bawang-patriot',
        'kue-kering-wijen',
        'sirup-rosela-embun',
        'kopi-susu-aren-literan',
        'teh-rempah-sejuk',
        'tunik-laras-harian',
        'pashmina-warna-bumi',
        'tas-kain-serbaguna',
        'bakul-bambu-modern',
        'lampu-meja-anyaman',
        'wadah-alat-tulis-bambu',
        'sabun-mandi-serai',
        'balm-bibir-madu',
        'lulur-rempah-tradisi',
        'paket-desain-undangan-digital',
        'cetak-stiker-usaha',
        'foto-produk-sederhana',
        'abon-jamur-gurih',
        'bawang-goreng-renyah',
        'kacang-bumbu-daun-jeruk',
        'sari-lemon-madu',
        'jahe-merah-instan',
        'kunyit-asam-segar',
        'outer-motif-geometris',
        'dompet-kain-etnik',
        'syal-tenun-ceria',
        'hiasan-dinding-makrame',
        'pot-kain-daur-ulang',
        'gantungan-kunci-rajut',
    ];

    private const PARTNERS = [
        'dapur-patriot-rasa' => ['name' => 'Dapur Patriot Rasa', 'whatsapp' => '628000000001'],
        'kedai-embun-bekasi' => ['name' => 'Kedai Embun Bekasi', 'whatsapp' => '628000000002'],
        'laras-busana-nusa' => ['name' => 'Laras Busana Nusa', 'whatsapp' => '628000000003'],
        'kriya-bambu-harapan' => ['name' => 'Kriya Bambu Harapan', 'whatsapp' => '628000000004'],
        'rona-cantik-alami' => ['name' => 'Rona Cantik Alami', 'whatsapp' => '628000000005'],
        'studio-kreasi-jatiasih' => ['name' => 'Studio Kreasi Jatiasih', 'whatsapp' => '628000000006'],
        'pangan-sejahtera-medan-satria' => ['name' => 'Pangan Sejahtera Medan Satria', 'whatsapp' => '628000000007'],
        'serambi-minuman-segar' => ['name' => 'Serambi Minuman Segar', 'whatsapp' => '628000000008'],
        'tenun-ceria-mustika' => ['name' => 'Tenun Ceria Mustika', 'whatsapp' => '628000000009'],
        'rumah-karya-pondok-melati' => ['name' => 'Rumah Karya Pondok Melati', 'whatsapp' => '628000000010'],
    ];

    private const PRODUCT_SHORT_DESCRIPTION = 'Produk dummy pilihan untuk demonstrasi katalog KUPATBekasi.';

    private const PRODUCT_DESCRIPTION = 'Data produk fiktif yang disiapkan khusus untuk kebutuhan demo dan screenshot.';

    private const PARTNER_SHORT_DESCRIPTION = 'UMKM dummy untuk demonstrasi katalog KUPATBekasi.';

    private const CATEGORY_SLUGS = [
        'makanan',
        'minuman',
        'fesyen',
        'kerajinan',
        'kecantikan',
        'jasa-dan-lainnya',
    ];

    private const BANNERS = [
        [
            'title' => 'Bangga Produk UMKM Kota Bekasi',
            'subtitle' => 'Temukan produk pilihan dari pelaku usaha lokal dalam katalog demo KUPATBekasi.',
            'button_label' => 'Lihat Produk',
            'button_url' => '/produk',
            'sort_order' => 1,
        ],
        [
            'title' => 'Jelajahi Karya Mitra Lokal',
            'subtitle' => 'Kenali ragam produk dan layanan kreatif UMKM di berbagai wilayah Kota Bekasi.',
            'button_label' => 'Lihat Mitra',
            'button_url' => '/mitra',
            'sort_order' => 2,
        ],
    ];

    private const SITE_SETTING_DEMO_VALUES = [
        'about_summary' => 'Data demonstrasi katalog digital untuk memperkenalkan produk UMKM lokal Kota Bekasi.',
        'contact_whatsapp' => '628000000000',
        'contact_email' => 'demo@kupatbekasi.test',
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Hapus seluruh data demo KUPATBekasi yang teridentifikasi?')) {
            $this->warn('Pembersihan dibatalkan. Tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        try {
            $counts = DB::transaction(function (): array {
                $productsDeleted = 0;
                $productsRetained = 0;
                $demoPartnerIds = Partner::withTrashed()
                    ->whereIn('slug', array_keys(self::PARTNERS))
                    ->pluck('id');

                Product::withTrashed()
                    ->whereIn('slug', self::PRODUCT_SLUGS)
                    ->get()
                    ->each(function (Product $product) use ($demoPartnerIds, &$productsDeleted, &$productsRetained): void {
                        $isDemo = $product->short_description === self::PRODUCT_SHORT_DESCRIPTION
                            && $product->description === self::PRODUCT_DESCRIPTION
                            && $demoPartnerIds->contains($product->partner_id);

                        if (! $isDemo) {
                            $productsRetained++;

                            return;
                        }

                        $product->forceDelete();
                        $productsDeleted++;
                    });

                $partnersDeleted = 0;
                $partnersRetained = 0;
                Partner::withTrashed()
                    ->whereIn('slug', array_keys(self::PARTNERS))
                    ->get()
                    ->each(function (Partner $partner) use (&$partnersDeleted, &$partnersRetained): void {
                        $fingerprint = self::PARTNERS[$partner->slug];
                        $isDemo = $partner->short_description === self::PARTNER_SHORT_DESCRIPTION
                            && $partner->description === "{$fingerprint['name']} adalah profil usaha fiktif untuk kebutuhan demonstrasi."
                            && $partner->whatsapp === $fingerprint['whatsapp'];

                        if (! $isDemo || $partner->products()->withTrashed()->exists()) {
                            $partnersRetained++;

                            return;
                        }

                        $partner->forceDelete();
                        $partnersDeleted++;
                    });

                $bannersDeleted = 0;
                foreach (self::BANNERS as $attributes) {
                    Banner::query()
                        ->where($attributes)
                        ->get()
                        ->each(function (Banner $banner) use (&$bannersDeleted): void {
                            $banner->delete();
                            $bannersDeleted++;
                        });
                }

                $siteSettingFieldsCleared = $this->cleanDemoSiteSettingFields();

                return [
                    'products' => $productsDeleted,
                    'products_retained' => $productsRetained,
                    'partners' => $partnersDeleted,
                    'partners_retained' => $partnersRetained,
                    'banners' => $bannersDeleted,
                    'site_setting_fields' => $siteSettingFieldsCleared,
                    'categories' => Category::query()->whereIn('slug', self::CATEGORY_SLUGS)->count(),
                    'users' => User::query()->count(),
                ];
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Pembersihan data demo gagal. Seluruh perubahan database telah dibatalkan.');

            return self::FAILURE;
        }

        $this->info("Product dihapus: {$counts['products']}");
        $this->info("Product ber-slug demo dipertahankan: {$counts['products_retained']}");
        $this->info("Partner dihapus: {$counts['partners']}");
        $this->info("Partner ber-slug demo dipertahankan: {$counts['partners_retained']}");
        $this->info("Banner dihapus: {$counts['banners']}");
        $this->info("Field SiteSetting dibersihkan: {$counts['site_setting_fields']}");
        $this->info("Category dipertahankan: {$counts['categories']}");
        $this->info("User tidak disentuh: {$counts['users']}");

        return self::SUCCESS;
    }

    private function cleanDemoSiteSettingFields(): int
    {
        $siteSetting = SiteSetting::query()->first();

        if (! $siteSetting) {
            return 0;
        }

        $cleared = 0;

        foreach (self::SITE_SETTING_DEMO_VALUES as $field => $demoValue) {
            if ($siteSetting->{$field} === $demoValue) {
                $siteSetting->{$field} = null;
                $cleared++;
            }
        }

        if ($cleared > 0) {
            $siteSetting->save();
        }

        return $cleared;
    }
}
