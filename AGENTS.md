# AGENTS.md
## Aturan Kerja Codex — KUPATBekasi

`docs/PRD.md` menentukan **apa yang dibangun**.  
`AGENTS.md` menentukan **bagaimana Codex bekerja**.

## 1. Misi

Bangun MVP KUPATBekasi sebagai katalog UMKM bergaya marketplace yang sederhana, cepat, responsif, aman, mudah dikelola, dan layak dipublikasikan pada 22 Juli 2026.

Prioritaskan hasil stabil. Jangan mengejar arsitektur ideal yang tidak diperlukan.

## 2. Sumber Kebenaran

Sebelum mengubah kode, baca:

1. `AGENTS.md`;
2. `docs/PRD.md`;
3. instruksi tugas terbaru;
4. kode dan test yang sudah ada.

Jika ada konflik:

- jangan menebak;
- jangan memperluas scope;
- laporkan konflik;
- pilih perubahan paling kecil hanya bila aman;
- jangan mengubah PRD atau `AGENTS.md` kecuali diminta.

## 3. Kerja Per Fase

Kerjakan hanya fase atau tugas kecil yang diminta.

Dilarang:

- mengerjakan fase berikutnya;
- membangun seluruh aplikasi dari satu prompt;
- “sekalian” menambah fitur;
- refactor luas yang tidak diperlukan;
- mengganti arsitektur karena preferensi;
- mengklaim pekerjaan berjalan di latar belakang.

Setelah tugas selesai, berhenti, laporkan hasil, dan tunggu instruksi.

## 4. Baseline Teknologi

- Laravel 12.
- PHP 8.2+.
- Filament 5.
- Blade.
- Tailwind CSS.
- Alpine.js.
- MySQL 8 atau MariaDB yang masih didukung.
- Vite.
- Git.

Versi aktual diperiksa pada preflight. Jangan menurunkan versi atau mengganti stack tanpa persetujuan.

Target produksi menggunakan MySQL 8 atau versi MariaDB yang masih didukung. MariaDB 10.4 lokal hanya boleh digunakan sementara untuk development.

### Jangan tambahkan

- React, Vue, Inertia, Next.js, Nuxt, atau SPA;
- REST API publik atau GraphQL;
- microservices;
- Redis;
- WebSocket;
- Laravel Octane;
- queue worker permanen;
- Docker produksi;
- multi-tenancy;
- package e-commerce/marketplace;
- package SEO/media manager besar.

Pengecualian memerlukan alasan konkret dan persetujuan.

## 5. Scope Guard

MVP adalah katalog dan direktori UMKM.

Jangan membuat:

- keranjang;
- checkout;
- pembayaran;
- pesanan;
- pengiriman;
- komisi;
- voucher;
- ulasan/rating;
- wishlist;
- login pengunjung;
- registrasi publik;
- dashboard mitra;
- chat internal;
- notifikasi WhatsApp otomatis;
- aplikasi mobile.

Tombol WhatsApp hanya membuka percakapan eksternal dengan pesan awal.

## 6. Preflight Wajib

Sebelum instalasi atau perubahan besar:

```bash
git status --short
php -v
composer --version
node -v
npm -v
```

Periksa juga:

- ekstensi PHP;
- database;
- isi repository;
- dokumentasi;
- `.gitignore`;
- kompatibilitas package;
- kemampuan hosting bila relevan.

Pada Fase 0:

- jangan membuat aplikasi;
- jangan memasang package;
- jangan mengubah file proyek;
- hanya audit dan laporkan.

Laporan preflight memuat versi, kompatibilitas, kekurangan, risiko, dan satu langkah kecil berikutnya.

## 7. Git dan Keselamatan

Sebelum mengubah file:

```bash
git status --short
```

Aturan:

- jangan menimpa perubahan pengguna;
- jangan `git reset --hard`;
- jangan `git clean -fd`;
- jangan force push;
- jangan menghapus branch atau history;
- jangan commit kecuali diminta;
- jangan commit `.env`, credential, dump, atau secret;
- jangan menghapus file tidak terkait.

Jika working tree tidak bersih, identifikasi perubahan dan hindari konflik.

## 8. Dependency

Sebelum menambah package:

1. pastikan fungsi tidak tersedia secara wajar pada stack utama;
2. jelaskan kebutuhan;
3. cek kompatibilitas dan lisensi;
4. minta persetujuan bila di luar baseline.

Jangan menambah package hanya untuk menghemat sedikit kode. Setelah dependency berubah, jalankan test/build dan laporkan alasannya.

## 9. Prinsip Implementasi

### Kesederhanaan

- Pilih solusi paling sederhana yang memenuhi PRD.
- Hindari overengineering.
- Jangan membuat abstraksi sebelum dibutuhkan.
- Jangan memakai repository layer untuk CRUD sederhana.
- Service class hanya untuk logika nyata yang dipakai ulang.

### Laravel

- Ikuti konvensi Laravel.
- Controller tipis.
- Form Request untuk validasi kompleks/digunakan ulang.
- Gunakan Eloquent relationship.
- Gunakan named routes.
- Route model binding publik memakai `slug`.
- Gunakan eager loading dan pagination.
- Jangan query database di Blade.
- Jangan membaca `env()` di luar config.
- Hindari raw SQL tanpa kebutuhan.

### Filament

- Gunakan Resource untuk CRUD.
- Schema form/table tetap sederhana.
- Jangan membuat custom page bila Resource cukup.
- Registrasi publik harus nonaktif.
- Panel hanya untuk user terautentikasi.
- Jangan memasang plugin Filament tanpa persetujuan.

### Blade dan frontend

- Frontend publik menggunakan Blade.
- Interaksi kecil menggunakan Alpine.js.
- Gunakan komponen Blade untuk elemen berulang.
- Escaping Blade adalah default.
- Jangan memakai raw HTML dari input admin.
- Prioritaskan mobile-first.

### CSS

- Gunakan Tailwind secara konsisten.
- Hindari inline style kecuali benar-benar dinamis.
- Jangan membuat sistem desain terlalu kompleks.
- Periksa 375×812, 768, dan 1366 piksel.

## 10. Database dan Migration

Migration harus:

- memiliki `up()` dan `down()` aman;
- menggunakan foreign key;
- memiliki index penting;
- menyimpan harga sebagai integer;
- tidak merusak data;
- tidak mengubah migration lama yang telah digunakan bersama;
- tidak destruktif tanpa backup/persetujuan.

Aturan:

- string untuk status sederhana;
- soft delete pada mitra/produk sesuai implementasi;
- jangan membuat tabel di luar PRD;
- seeder tidak memakai data pribadi nyata.

Pada database development/testing:

```bash
php artisan migrate:fresh --seed
php artisan test
```

Jangan pernah menjalankan `migrate:fresh` pada produksi.

## 11. Dummy Data

Target:

- 10 mitra;
- 30 produk, tepat 3 per mitra;
- 6 kategori;
- minimal 2 banner;
- 1 site setting.

Aturan:

- tidak menggunakan nomor, identitas, atau logo nyata tanpa izin;
- gunakan placeholder lokal/aset legal;
- tandai sebagai demonstrasi;
- seed konsisten;
- jangan simpan password produksi;
- seed dummy tidak dijalankan di produksi tanpa instruksi.

## 12. Upload dan Media

- Gunakan disk `public`.
- Format JPEG, PNG, dan WebP.
- Maksimum 2 MB.
- Jangan percaya nama file asli.
- Gunakan path terstruktur.
- Sediakan placeholder lokal.
- Jangan hotlink gambar eksternal.
- Jangan memasang package pemrosesan gambar tanpa persetujuan.
- Sertakan `storage:link` pada deployment.
- Jangan menghapus file yang mungkin masih dipakai record lain.

## 13. WhatsApp

Normalisasi nomor harus berada pada satu lokasi yang dapat diuji.

Aturan:

- hapus karakter nonangka;
- awalan `0` menjadi `62`;
- awalan `62` dipertahankan;
- nomor tidak valid tidak menghasilkan link;
- encode pesan dengan benar;
- jangan mengirim pesan otomatis;
- jangan menyimpan percakapan.

Pesan:

```text
Halo, saya melihat produk [NAMA PRODUK] di KUPATBekasi. Apakah produk ini masih tersedia?
```

## 14. Keamanan

Wajib:

- validasi input;
- CSRF;
- Blade escaping;
- pembatasan upload;
- registrasi publik nonaktif;
- panel admin terlindungi;
- tidak menyimpan/log secret;
- `APP_DEBUG=false` pada produksi;
- document root ke `public`;
- tidak menyediakan editor HTML bebas;
- hindari `{!! !!}`;
- mass assignment aman.

Jangan membuat sistem role/permission kompleks untuk satu jenis admin.

## 15. Testing

Gunakan test runner yang telah dipilih proyek. Jangan menggantinya di tengah proyek tanpa alasan.

Setiap perubahan perilaku memiliki test proporsional.

Sebelum fase dinyatakan selesai:

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

Gunakan test target saat implementasi, lalu full suite pada akhir fase.

Jika test gagal:

- jangan menyembunyikan kegagalan;
- jangan menghapus test agar hijau;
- perbaiki akar masalah dalam scope;
- laporkan yang belum terselesaikan.

Jangan mengklaim lulus bila perintah belum dijalankan.

## 16. Pemeriksaan Manual

Periksa minimal:

- 375×812;
- 768 piksel;
- 1366 piksel.

Periksa navigasi, search, filter, pagination, empty state, detail produk, profil mitra, WhatsApp, placeholder, footer, overflow, fokus keyboard, dan kontras.

Jangan menyatakan UI selesai hanya berdasarkan kode.

## 17. Performa

- Eager loading.
- Hindari N+1.
- Batasi query/item beranda.
- Pagination.
- Jangan memuat galeri penuh di kartu.
- Hindari library frontend besar.
- Gunakan build Vite produksi.
- Jangan menjalankan `npm run dev` di produksi.
- Jangan menambah Redis/CDN berbayar untuk MVP.

Jangan melakukan optimasi spekulatif.

## 18. Deployment cPanel

Hanya folder `public` menjadi document root.

Dilarang:

- mengekspos seluruh proyek;
- mengekspos `.env`;
- menjalankan `migrate:fresh`;
- seed dummy tanpa instruksi;
- `APP_DEBUG=true`;
- menyimpan password pada script;
- menjalankan server development di produksi.

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

Sebelum deployment: backup dan konfirmasi environment, database, domain, SSL, serta document root.

Setelah deployment: smoke test dan backup awal.

Jika cPanel tidak dapat mengarahkan document root ke `public`, jangan membuat workaround diam-diam. Laporkan dan ajukan pola aman.

## 19. Dokumentasi

Perbarui dokumentasi hanya bila perubahan memengaruhi instalasi, environment, migration, deployment, fitur, atau acceptance criteria.

Jangan mengubah PRD untuk membenarkan implementasi yang menyimpang.

Komentar kode menjelaskan alasan, bukan mengulang kode.

## 20. Format Laporan

```text
Status:
- Selesai / Sebagian / Terhambat

Perubahan:
- ...

File utama:
- ...

Validasi:
- [perintah] — lulus/gagal

Risiko/catatan:
- ...

Langkah berikutnya:
- Satu langkah kecil yang disarankan.
```

Laporan harus jujur dan hanya menyebut perintah yang benar-benar dijalankan.

## 21. Definition of Done

Tugas selesai hanya bila:

- sesuai fase aktif dan PRD;
- scope tidak bertambah;
- kode mengikuti konvensi;
- migration aman;
- test relevan tersedia;
- test, Pint, dan build yang diwajibkan lulus;
- tidak ada secret;
- perubahan pengguna tidak tertimpa;
- laporan akhir diberikan.

Jika belum, status adalah “Sebagian” atau “Terhambat”.

## 22. Prinsip Akhir

> Bangun yang dibutuhkan untuk publikasi, bukan semua yang mungkin dibangun.

Jika ada dua solusi yang memenuhi kebutuhan, pilih yang:

1. lebih sederhana;
2. lebih mudah diuji;
3. lebih mudah dipasang di cPanel;
4. memakai dependency lebih sedikit;
5. paling kecil risikonya.
