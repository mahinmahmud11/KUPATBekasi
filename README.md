# KUPATBekasi

## Deskripsi
KUPATBekasi (Katalog UMKM Patriot Binaan Kota Bekasi) adalah aplikasi katalog digital berbasis web yang ditujukan untuk menampilkan dan mempromosikan produk serta profil para pelaku Usaha Mikro, Kecil, dan Menengah (UMKM) di wilayah Kota Bekasi. Aplikasi ini mempermudah masyarakat menemukan berbagai produk unggulan daerah.

Fitur utama aplikasi ini meliputi:
- Katalog produk UMKM yang dilengkapi dengan filter berdasarkan kategori dan mitra.
- Halaman profil mitra lokal beserta rincian kontak dan daftar produk.
- Halaman informasi dan kontak dengan kustomisasi melalui panel administrator.
- Integrasi tombol WhatsApp pada produk untuk memulai komunikasi dengan penjual.
- Panel administrator untuk pengelolaan data spanduk, mitra, dan produk.

## Teknologi

| Nama Teknologi | Peran | Versi |
|----------------|-------|-------|
| Laravel | Web Framework | 12.0 |
| Filament | Admin Panel | 5.0 |
| PHP | Server-Side Language | ^8.2 |
| Vite | Frontend Build Tool | ^6.0.11 |
| Tailwind CSS | Utility-First CSS Framework | ^4.0.0 |
| MySQL/MariaDB | Database Utama (Produksi) | Didukung |
| SQLite | Database Bawaan (Localhost) | Didukung |

## Persyaratan Sistem

Untuk menjalankan proyek ini, dibutuhkan minimum lingkungan berikut:
- PHP >= 8.2 (dengan ekstensi standar Laravel; untuk database aktifkan `pdo_sqlite` jika menggunakan SQLite, atau `pdo_mysql` jika menggunakan MySQL/MariaDB)
- Composer 2.x
- Node.js (disarankan versi LTS terbaru) dan npm
- Git
- Database pendukung (SQLite, MySQL, atau MariaDB)

## Instalasi Localhost

Ikuti langkah-langkah di bawah untuk mengatur lingkungan *development* pada komputer Anda:

1. **Clone repository**
   Unduh repository ke mesin lokal Anda:
   ```bash
   git clone https://github.com/mahinmahmud11/KUPATBekasi.git
   cd KUPATBekasi
   ```

2. **Install dependency PHP**
   Unduh paket pustaka backend:
   ```bash
   composer install
   ```

3. **Install dependency Node.js**
   Unduh modul frontend:
   ```bash
   npm install
   ```

4. **Konfigurasi Environment**
   Salin file konfigurasi.
   - Windows PowerShell:
     ```powershell
     Copy-Item .env.example .env
     ```
   - Linux/macOS:
     ```bash
     cp .env.example .env
     ```

5. **Konfigurasi Database**
   Secara konfigurasi awal, `.env.example` menggunakan SQLite, yang cocok untuk pengembangan lokal. Ini bukan satu-satunya pilihan. Anda juga dapat menggunakan MySQL/MariaDB dengan menyesuaikan parameter `DB_*` di file `.env`.

   Jika Anda mempertahankan SQLite, buat file databasenya.
   - Windows PowerShell:
     ```powershell
     New-Item database/database.sqlite -ItemType File -Force
     ```
   - Linux/macOS:
     ```bash
     touch database/database.sqlite
     ```

   Contoh konfigurasi `.env` untuk SQLite yang relevan:
   ```ini
   DB_CONNECTION=sqlite
   # DB_HOST=127.0.0.1
   # DB_PORT=3306
   # DB_DATABASE=laravel
   # DB_USERNAME=root
   # DB_PASSWORD=
   ```

6. **Generate App Key**
   Buat kunci enkripsi aplikasi:
   ```bash
   php artisan key:generate
   ```

7. **Migrasi dan Seed Data**
   Jalankan migrasi skema tabel dan isi data awal (dummy):
   ```bash
   php artisan migrate --seed
   ```

8. **Buat Akun Administrator**
   Aplikasi ini tidak menyertakan akun admin bawaan pada seeder. Anda perlu membuatnya manual untuk mengakses Filament:
   ```bash
   php artisan kupat:make-admin
   ```
   Perintah ini memastikan akun baru atau akun lama yang Anda masukkan dipromosikan dengan hak akses administrator (`is_admin=true`).

9. **Storage Link**
   Buat tautan agar file *storage* dapat diakses publik:
   ```bash
   php artisan storage:link
   ```

10. **Jalankan Aplikasi**
    Buka terminal dan eksekusi skrip:
    ```bash
    composer run dev
    ```

    Setelah skrip berjalan, aplikasi umumnya dapat diakses melalui:
    ```text
    http://127.0.0.1:8000
    ```
    Alamat aktual mengikuti keluaran terminal apabila *port* default sedang digunakan aplikasi lain.

## Menjalankan Development

Skrip `composer run dev` mengeksekusi beberapa perintah secara paralel menggunakan *concurrently*. Fungsi dari masing-masing proses adalah:

- **Laravel Development Server (`php artisan serve`)**: Menyediakan web server lokal.
- **Queue Worker (`php artisan queue:listen`)**: Mendengarkan antrean tugas secara aktif, siap memproses setiap entri *job* yang tertunda.
- **Pail (`php artisan pail`)**: Menampilkan *log* aplikasi interaktif pada terminal.
- **Vite (`npm run dev`)**: Menjalankan server *bundler* frontend untuk kompilasi *Hot Module Replacement* (HMR).

## Deployment VPS

Penting: Sebelum melakukan instalasi, pastikan pengaturan *Document Root* pada *web server* (Apache/Nginx) menunjuk ke direktori `public`, contohnya `/path/to/KUPATBekasi/public`.

### Instalasi Pertama

1. Clone repository ke direktori proyek (`<direktori-proyek>`):
   ```bash
   git clone https://github.com/mahinmahmud11/KUPATBekasi.git <direktori-proyek>
   cd <direktori-proyek>
   ```
2. Install dependency PHP untuk produksi:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Install modul Node:
   ```bash
   npm ci
   ```
4. Salin file konfigurasi environment:
   ```bash
   cp .env.example .env
   ```

   Kemudian atur parameter production menggunakan nilai yang sesuai:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-anda.example

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nama_database
   DB_USERNAME=nama_pengguna_database
   DB_PASSWORD=ganti_dengan_password_yang_kuat
   ```
   **Peringatan:** Nilai di atas hanya contoh. Jangan menyalin password contoh sebagai password nyata, dan jangan pernah memasukkan file `.env` *production* ke dalam Git.

5. Persiapkan aplikasi sesuai urutan berikut:
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   npm run build
   php artisan optimize:clear
   php artisan optimize
   ```
6. Terakhir, sesuaikan hak akses (*permission*) direktori sistem:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

### Update Deployment

Untuk memperbarui aplikasi produksi, jalankan langkah berikut dari direktori proyek:

```bash
git pull --ff-only origin main

composer install --no-dev --optimize-autoloader

npm ci

npm run build

php artisan optimize:clear

php artisan migrate --force

php artisan optimize

php artisan queue:restart
```

Catatan Update:
- Lakukan *backup* database sebelum menjalankan migrasi.
- Jangan menjalankan perintah `key:generate` saat melakukan *update*.
- Pastikan semua perintah dijalankan dari dalam direktori proyek.
- Apabila `git pull` gagal akibat adanya perubahan file lokal, hentikan proses deployment dan lakukan audit pada perubahan tersebut. Jangan memaksakan reset tanpa verifikasi.

## Queue Worker

Aplikasi menggunakan sistem antrean berbasis database, berdasarkan konfigurasi bawaan `QUEUE_CONNECTION=database`. Pastikan Anda memiliki *queue worker* yang selalu berjalan di VPS.

Contoh konfigurasi **Supervisor** (`/etc/supervisor/conf.d/kupatbekasi-worker.conf`):

```ini
[program:kupatbekasi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kupatbekasi/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/kupatbekasi/storage/logs/worker.log
stopwaitsecs=3600
```

Terapkan konfigurasi:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kupatbekasi-worker:*
sudo supervisorctl status
```

Status proses harus menunjukkan `RUNNING`. Pastikan Anda menyesuaikan `/var/www/kupatbekasi` dengan direktori proyek aktual, dan sesuaikan user `www-data` apabila PHP-FPM atau web server menggunakan user yang berbeda.

## Storage

File *upload* publik (seperti gambar produk atau profil mitra) disimpan pada `storage/app/public`.

Perintah `php artisan storage:link` berfungsi membuat *symbolic link* agar aset tersebut dapat diakses melalui internet, yang menghubungkan:
`public/storage` -> `storage/app/public`

Perintah `storage:link` cukup dijalankan saat instalasi pertama atau ketika *symbolic link* belum ada atau rusak.
Folder `storage` dan `bootstrap/cache` harus dapat ditulis oleh user web server agar aplikasi dapat menyimpan log, *cache*, maupun file *upload*. Jangan menggunakan *permission* `777`.

Sertakan perintah VPS:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Scheduler

Saat ini, file `routes/console.php` belum mendefinisikan jadwal aplikasi khusus (Task Scheduling). Konfigurasi *cron* baru akan diperlukan apabila ada *scheduler* yang ditambahkan di kemudian hari.

## Troubleshooting

- **`APP_KEY` missing (Error 500)**: Aplikasi membutuhkan kunci enkripsi. Jalankan `php artisan key:generate`.
- **Izin akses ditolak (Permission denied)**: Pastikan konfigurasi *chown* dan *chmod* pada folder `storage` dan `bootstrap/cache` sudah benar.
- **Storage image tidak muncul**: *Symlink* kemungkinan rusak atau belum ada. Perbaiki dengan menjalankan `php artisan storage:link`.
- **Aset antarmuka tidak dimuat (Vite manifest not found)**: Pastikan telah menjalankan *build* aset. Anda dapat memeriksa keberadaan file di `public/build/manifest.json`.
- **Cache konfigurasi tidak sinkron**: Jika konfigurasi gagal dimuat, bersihkan menggunakan `php artisan optimize:clear`.
- **Job antrean tidak tereksekusi**:
  - Pastikan *worker* aktif menggunakan `supervisorctl status`.
  - Periksa tugas yang gagal eksekusi dengan `php artisan queue:failed`.

## Security Notes

- **Jangan *commit* `.env`**: Repositori Git tidak boleh menyimpan rahasia konfigurasi peladen Anda.
- **Environment Produksi**: Pastikan nilai `APP_DEBUG=false` untuk mencegah kebocoran *stack trace* dan konfigurasi ke pengguna akhir.
- **Gunakan HTTPS**: Selalu pastikan web server memfasilitasi HTTPS yang valid agar lalu lintas data dan kredensial terlindungi.
- **Backup Database**: Cadangkan database secara reguler, terutama sebelum mengeksekusi `migrate` pada lingkungan produksi.
- **Hindari `key:generate` pada Update Deployment**: Menjalankan ulang perintah pembuatan kunci akan mengubah `APP_KEY`, yang dapat membuat session, cookie terenkripsi, dan data terenkripsi lama tidak dapat dibaca.

## License

Hak penggunaan dan distribusi proyek mengikuti kebijakan pemilik repository. Hubungi pemilik proyek untuk informasi lisensi.
