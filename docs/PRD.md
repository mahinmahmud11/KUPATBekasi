# Product Requirements Document (PRD) Teknis
## KUPATBekasi — Katalog UMKM Patriot Binaan Kota Bekasi

| Informasi | Nilai |
|---|---|
| Versi | 1.0 |
| Tanggal | 20 Juli 2026 |
| Target publikasi | 22 Juli 2026 |
| Klien | Instansi Pemerintah Kota Bekasi |
| Jenis produk | Katalog UMKM bergaya marketplace |
| Status | Baseline pengembangan untuk Codex |

---

## 1. Ringkasan

KUPATBekasi adalah aplikasi web katalog digital untuk mempromosikan UMKM binaan Kota Bekasi beserta produknya. Tampilan publik memberikan pengalaman seperti marketplace modern, tetapi MVP **tidak memproses transaksi di dalam aplikasi**.

Pengunjung dapat melihat, mencari, dan memfilter produk; membuka profil mitra; melihat detail produk; lalu menghubungi mitra melalui WhatsApp. Administrator mengelola data melalui panel admin.

> Keputusan utama: KUPATBekasi versi 1 adalah katalog promosi dan direktori UMKM. Pemesanan dilanjutkan langsung melalui WhatsApp masing-masing mitra.

## 2. Tujuan

### Tujuan bisnis

- Menyediakan etalase digital terpadu bagi UMKM binaan Kota Bekasi.
- Mempermudah masyarakat menemukan produk UMKM lokal.
- Memperkuat citra program pembinaan UMKM Pemerintah Kota Bekasi.
- Menyediakan sistem yang mudah dikelola administrator nonteknis.
- Menghasilkan MVP yang layak dipublikasikan pada 22 Juli 2026.

### Tujuan teknis

- Aplikasi monolitik yang sederhana dan mudah dipelihara.
- Cepat serta responsif pada perangkat seluler.
- Panel admin siap pakai tanpa membangun CRUD dari nol.
- Dapat dipasang pada hosting cPanel yang mendukung Laravel.
- Menjaga scope agar tidak berubah menjadi marketplace transaksi penuh.

## 3. Pengguna

1. **Pengunjung umum:** mencari produk dan menghubungi penjual.
2. **Mitra UMKM:** profil dan produknya ditampilkan; belum memiliki akun.
3. **Administrator:** mengelola seluruh konten melalui `/admin`.

## 4. Ruang Lingkup MVP

### Termasuk

- Beranda.
- Katalog produk.
- Pencarian produk dan mitra.
- Filter kategori.
- Detail produk.
- Daftar dan profil mitra.
- Tombol WhatsApp.
- Halaman Tentang, Kontak, dan Kebijakan Privasi.
- Panel admin.
- CRUD mitra, kategori, produk, galeri, banner, dan pengaturan situs.
- Seed 10 mitra, 30 produk, 6 kategori, dan minimal 2 banner.
- Tampilan responsif.
- SEO dasar.
- Deployment ke hosting.

### Tidak termasuk

- Registrasi/login pengunjung.
- Registrasi dan dashboard mitra.
- Keranjang, checkout, pembayaran, pesanan, dan pengiriman.
- Komisi, voucher, rating, ulasan, wishlist, serta chat internal.
- REST API publik.
- React, Vue, Inertia, SPA, atau aplikasi mobile.
- Multi-tenancy dan microservices.
- Notifikasi WhatsApp otomatis.

Fitur di luar daftar “Termasuk” hanya boleh dikerjakan setelah perubahan scope disetujui.

## 5. Stack Teknologi

### Baseline

- Laravel 12.
- PHP 8.2 atau versi kompatibel yang lebih tinggi.
- Filament 5.
- Blade.
- Tailwind CSS.
- Alpine.js.
- MySQL 8 atau MariaDB yang masih didukung.
- Vite.
- Laravel public storage.
- Git.

Versi aktual wajib diverifikasi pada Fase 0.

Target produksi menggunakan MySQL 8 atau versi MariaDB yang masih didukung. MariaDB 10.4 lokal hanya boleh digunakan sementara untuk development.

### Larangan arsitektur

- Tidak membuat frontend/backend terpisah.
- Tidak membuat API hanya untuk frontend internal.
- Tidak menggunakan SPA atau microservices.
- Tidak menambahkan Redis, WebSocket, Octane, queue permanen, atau Docker produksi tanpa kebutuhan yang disetujui.
- Tidak memasang package untuk fungsi yang dapat ditangani stack utama.

## 6. Arsitektur

```text
Pengunjung
    ↓
Routes → Controllers → Eloquent → MySQL
    ↓
Blade + Tailwind + Alpine.js

Administrator
    ↓
Filament Panel
    ↓
Eloquent → MySQL
```

Prinsip:

- Controller tipis.
- Form Request untuk validasi yang kompleks/digunakan ulang.
- Eloquent relationship sebagai relasi utama.
- Service hanya untuk logika yang nyata dan digunakan ulang.
- Tidak memakai repository pattern untuk CRUD sederhana.
- Route model binding menggunakan `slug`.
- Semua halaman publik server-rendered.
- Tidak melakukan query di Blade.

## 7. Model Data

### `users`

Menggunakan autentikasi Laravel untuk administrator. Tidak ada registrasi publik.

### `partners`

- `id`
- `name` wajib
- `slug` unik
- `owner_name` nullable
- `short_description` nullable
- `description` nullable
- `address` nullable
- `district` nullable
- `whatsapp` wajib
- `instagram_url` nullable
- `logo_path` nullable
- `cover_path` nullable
- `is_featured` default false
- `is_active` default true
- `sort_order` default 0
- timestamps
- soft deletes disarankan

### `categories`

- `id`
- `name` wajib
- `slug` unik
- `description` nullable
- `icon` nullable
- `is_active` default true
- `sort_order` default 0
- timestamps

### `products`

- `id`
- `partner_id` foreign key
- `category_id` foreign key
- `name` wajib
- `slug` unik
- `short_description` nullable
- `description` nullable
- `price` unsigned big integer
- `unit` nullable
- `main_image_path` nullable
- `stock_status` default `available`
- `is_featured` default false
- `is_active` default true
- `sort_order` default 0
- timestamps
- soft deletes disarankan

Harga disimpan sebagai integer rupiah, bukan floating point.

### `product_images`

- `id`
- `product_id` foreign key, cascade delete
- `image_path`
- `alt_text` nullable
- `sort_order` default 0
- timestamps

### `banners`

- `id`
- `title`
- `subtitle` nullable
- `image_path` nullable
- `button_label` nullable
- `button_url` nullable
- `is_active` default true
- `sort_order` default 0
- timestamps

### `site_settings`

Satu record pengaturan:

- `site_name`
- `tagline` nullable
- `about_summary` nullable
- `contact_whatsapp` nullable
- `contact_email` nullable
- `address` nullable
- `instagram_url` nullable
- `logo_path` nullable
- `favicon_path` nullable
- timestamps

### Relasi

```text
Partner 1 ─── * Product
Category 1 ── * Product
Product 1 ─── * ProductImage
```

Tambahkan index pada slug, foreign key, `is_active`, dan `is_featured`.

## 8. Route Publik

| URL | Fungsi | Nama route |
|---|---|---|
| `/` | Beranda | `home` |
| `/produk` | Katalog | `products.index` |
| `/produk/{product:slug}` | Detail produk | `products.show` |
| `/mitra` | Daftar mitra | `partners.index` |
| `/mitra/{partner:slug}` | Profil mitra | `partners.show` |
| `/kategori/{category:slug}` | Produk per kategori | `categories.show` |
| `/tentang` | Tentang | `about` |
| `/kontak` | Kontak | `contact` |
| `/kebijakan-privasi` | Privasi | `privacy` |
| `/admin` | Panel admin | Filament |

Produk atau mitra nonaktif tidak boleh terlihat di publik.

## 9. Fitur Publik

### Beranda

Menampilkan:

1. header dan navigasi;
2. hero banner;
3. pencarian;
4. kategori aktif;
5. produk unggulan;
6. mitra unggulan;
7. produk terbaru;
8. ringkasan program;
9. CTA katalog;
10. footer instansi.

Kondisi kosong tidak boleh menyebabkan error atau layout rusak.

### Katalog produk

- Grid kartu responsif.
- Kartu: foto, nama, harga rupiah, nama mitra, dan kecamatan.
- Pencarian berdasarkan nama produk, nama mitra, dan deskripsi singkat.
- Filter satu kategori.
- Pagination.
- Query tetap dipertahankan saat pindah halaman.
- Empty state informatif.
- Urutan: aktif, unggulan, `sort_order`, lalu terbaru.

Contoh:

```text
/produk?q=keripik&category=makanan
```

### Detail produk

- Nama, harga, foto utama, galeri, deskripsi, kategori, dan status.
- Ringkasan mitra serta tautan ke profil.
- Tombol WhatsApp.
- Produk terkait dari kategori/mitra yang sama.

Pesan awal:

```text
Halo, saya melihat produk [NAMA PRODUK] di KUPATBekasi. Apakah produk ini masih tersedia?
```

Normalisasi WhatsApp:

- hapus karakter nonangka;
- awalan `0` diubah menjadi `62`;
- nomor `62` dipertahankan;
- link tidak dibuat bila nomor tidak valid;
- pesan di-URL-encode.

### Mitra

Daftar menampilkan logo/foto, nama, deskripsi pendek, kecamatan, dan jumlah produk aktif.

Profil menampilkan nama usaha, logo/cover, deskripsi, alamat, kecamatan, WhatsApp, Instagram, label “UMKM Binaan Kota Bekasi”, dan produk aktif milik mitra.

### Halaman informasi

- Tentang program.
- Kontak resmi.
- Kebijakan privasi.
- Disclaimer bahwa transaksi dilakukan langsung antara pengunjung dan UMKM.

## 10. Panel Administrator

Gunakan Filament.

Resource wajib:

- Partner Resource.
- Category Resource.
- Product Resource.
- Banner Resource.
- Site Settings Page/Resource satu record.

Kemampuan:

- daftar, cari, filter, tambah, ubah, hapus/nonaktifkan;
- upload gambar;
- menetapkan unggulan;
- mengatur urutan;
- mengelola relasi produk–mitra–kategori.

Aturan:

- Tidak ada registrasi admin publik.
- Password asli tidak ditulis di repository.
- Penghapusan relasional memiliki konfirmasi.
- Data nonaktif tidak tampil di publik.

## 11. Media

Jenis:

- logo/favikon;
- banner;
- logo dan cover mitra;
- foto utama dan galeri produk.

Validasi:

- JPEG, PNG, WebP;
- maksimum 2 MB/file;
- disk `public`;
- nama file aman;
- `storage:link` saat deployment;
- placeholder lokal bila gambar kosong;
- tidak melakukan hotlink dari layanan eksternal.

## 12. Desain dan UX

- Modern, bersih, dan layak untuk instansi pemerintah.
- Tidak meniru identitas Tokopedia.
- Warna: hijau, biru tua, putih, aksen kuning keemasan.
- Mobile-first.
- Viewport wajib: 375×812, 768, dan 1366 piksel.
- Kontras memadai, fokus keyboard terlihat, gambar memiliki `alt`, heading berurutan, dan form memiliki label.
- Komponen minimum: header, hero, search, kategori, kartu produk, kartu mitra, WhatsApp CTA, empty state, pagination, dan footer.

## 13. SEO Dasar

- Judul dan meta description unik.
- Slug manusiawi.
- Open Graph dasar.
- Canonical bila sederhana.
- Data nonaktif tidak dapat diakses.
- Jangan memasang package SEO besar tanpa persetujuan.

## 14. Keamanan

- Autentikasi Filament.
- Registrasi publik nonaktif.
- Validasi semua input.
- CSRF aktif.
- Blade escaping default.
- Hindari raw HTML.
- Secret hanya di `.env`.
- `.env` tidak di-commit.
- `APP_DEBUG=false` pada produksi.
- Upload dibatasi.
- Mass assignment aman.
- Document root hanya folder `public`.
- Tidak menyediakan editor HTML bebas pada MVP.

## 15. Performa

- Eager loading untuk mencegah N+1.
- Pagination.
- Jumlah item beranda dibatasi.
- Gambar berukuran wajar.
- Build produksi Vite.
- Cache konfigurasi, route, dan view saat deployment.
- Tidak memakai Redis/CDN berbayar pada MVP.

## 16. Seed Data

Wajib:

- 10 mitra dummy;
- 30 produk dummy, tepat 3 per mitra;
- 6 kategori;
- minimal 2 banner;
- 1 site setting;
- kombinasi data unggulan dan biasa.

Kategori:

1. Makanan
2. Minuman
3. Fesyen
4. Kerajinan
5. Kecantikan
6. Jasa dan Lainnya

Ketentuan:

- idempotent/aman dijalankan ulang pada development;
- tidak memakai identitas, nomor, atau logo nyata tanpa izin;
- gambar legal atau placeholder lokal;
- tidak menulis password produksi;
- tidak menjalankan seed dummy di produksi tanpa keputusan eksplisit.

## 17. Testing

Minimal:

### Publik

- beranda dapat diakses;
- katalog hanya menampilkan produk aktif;
- pencarian bekerja;
- filter kategori bekerja;
- detail berdasarkan slug bekerja;
- produk nonaktif tidak dapat diakses;
- daftar mitra hanya menampilkan mitra aktif;
- profil menampilkan produk aktif;
- normalisasi WhatsApp benar;
- halaman informasi dapat diakses.

### Admin

- tamu tidak dapat membuka panel;
- admin dapat login;
- validasi penting bekerja;
- tidak ada registrasi publik.

### Data

- relationship dan foreign key benar;
- seed menghasilkan 10 mitra, 30 produk, dan 6 kategori;
- factory menghasilkan data valid.

Pemeriksaan akhir fase:

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

## 18. Deployment cPanel

Persyaratan:

- PHP 8.2+;
- MySQL 8 atau MariaDB yang masih didukung;
- Composer 2;
- SSL;
- permission folder;
- terminal/SSH atau prosedur deployment terverifikasi;
- document root ke `public`;
- memory limit disarankan minimal 256 MB.

Struktur:

```text
/home/USERNAME/kupatbekasi/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
└── .env
```

Document root:

```text
/home/USERNAME/kupatbekasi/public
```

Perintah baseline:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Aset:

```bash
npm ci
npm run build
```

Produksi minimal:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-resmi
LOG_LEVEL=warning
```

Setelah deployment: uji HTTPS, halaman publik, WhatsApp, login admin, upload gambar, debug, dan backup awal.

## 19. Fase Pengerjaan

### Fase 0 — Preflight
Audit Git, PHP, Composer, Node, npm, database, ekstensi, dan hosting. Belum mengubah proyek.

### Fase 1 — Inisialisasi
Buat Laravel, pasang Filament, konfigurasi development, dan verifikasi baseline.

### Fase 2 — Database
Migration, model, relasi, factory, seeder dasar, dan test data.

### Fase 3 — Admin
Filament resources, validasi, upload, dan test akses/CRUD.

### Fase 4 — Frontend
Layout, beranda, katalog, detail, mitra, pencarian, filter, pagination, dan WhatsApp.

### Fase 5 — Dummy Content
Lengkapi 10 mitra, 30 produk, kategori, banner, dan settings.

### Fase 6 — QA
Test penuh, Pint, build, N+1, mobile, aksesibilitas dasar, dan keamanan.

### Fase 7 — Deployment
Backup, deploy cPanel, `.env`, migration, storage link, optimize, smoke test, backup akhir.

Setiap fase harus dilaporkan dan disetujui sebelum fase berikutnya.

## 20. Kriteria Penerimaan MVP

- Domain HTTPS aktif.
- Beranda baik pada mobile/desktop.
- Tepat 10 mitra, 30 produk, dan 6 kategori dummy.
- Pencarian, kategori, pagination, detail, profil mitra, dan WhatsApp bekerja.
- Data nonaktif tidak tampil.
- Admin dapat mengelola seluruh modul wajib.
- Tidak ada registrasi publik.
- Tidak ada keranjang, checkout, pembayaran, atau pesanan.
- Test, Pint, dan build lulus.
- Tidak ada error kritis.
- `APP_DEBUG=false`.
- Backup awal tersedia.

## 21. Definition of Done

Tugas hanya selesai jika:

1. sesuai PRD dan fase aktif;
2. tidak menambah scope;
3. migration aman;
4. test relevan tersedia;
5. test target dan full suite lulus sesuai tahap;
6. Pint lulus;
7. build lulus bila frontend berubah;
8. tidak ada secret;
9. laporan perubahan, test, risiko, dan langkah berikutnya diberikan.

## 22. Ketergantungan Klien

Klien menyediakan domain, hosting, akses cPanel/SSH, identitas resmi, kontak, data UMKM final, foto berizin, serta persetujuan disclaimer dan privasi.

## 23. Referensi Resmi

- Laravel 12 Release Notes: `https://laravel.com/docs/12.x/releases`
- Laravel Deployment: `https://laravel.com/docs/12.x/deployment`
- Filament 5 Installation: `https://filamentphp.com/docs/5.x/introduction/installation`
- Filament 5 Deployment: `https://filamentphp.com/docs/5.x/deployment`
