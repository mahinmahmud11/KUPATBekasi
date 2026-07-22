# Deployment KUPATBekasi ke VPS Linux

Dokumen ini adalah runbook deployment produksi KUPATBekasi ke VPS tanpa panel. Jalankan setiap langkah dengan menyesuaikan nama domain, user Linux, versi socket PHP-FPM, serta nama database pada server tujuan.

## 1. Asumsi dan ruang lingkup

Runbook ini memakai asumsi awal berikut:

- VPS menjalankan Ubuntu atau Debian;
- Nginx sebagai web server;
- PHP-FPM menjalankan aplikasi Laravel;
- MySQL 8 atau MariaDB yang masih didukung sebagai database;
- deployment dilakukan melalui SSH;
- SSL diterbitkan menggunakan Let's Encrypt dan Certbot;
- source ditempatkan di `/var/www/kupatbekasi/current`;
- user deployment pada contoh bernama `deploy`, sedangkan group web server adalah `www-data`.

Sesuaikan nama paket, lokasi konfigurasi, service, dan perintah firewall bila provider, distribusi Linux, atau topologi final berbeda. Runbook ini tidak membuat sistem zero-downtime atau release orchestration yang kompleks karena belum diperlukan untuk MVP.

## 2. Arsitektur produksi

```text
Internet (HTTPS)
       |
     Nginx
       |
    PHP-FPM
       |
Laravel 12 + Filament 5
       |
MySQL 8 / MariaDB yang masih didukung

Upload publik -> storage/app/public
URL media     -> public/storage (symbolic link)
Document root -> /var/www/kupatbekasi/current/public
```

Nginx hanya boleh mengekspos direktori `public`. Root repository, `.env`, `storage`, source PHP, backup, dan file operasional lain tidak boleh menjadi document root.

## 3. Persyaratan VPS

### Runtime aplikasi

`composer.json` menetapkan PHP `^8.2`, Laravel `^12.0`, dan Filament `~5.0`. Gunakan PHP 8.2 atau versi lebih baru yang masih kompatibel dengan dependency terkunci. Jangan memilih versi hanya berdasarkan ketersediaan socket; verifikasi dengan Composer pada server.

Platform requirement non-development yang terdeteksi dari dependency terkunci:

- Ctype;
- DOM;
- Fileinfo;
- Filter;
- Hash;
- Iconv;
- Intl;
- JSON;
- Libxml;
- Mbstring;
- OpenSSL;
- PCRE;
- Session;
- Tokenizer;
- XMLReader;
- ZIP.

Sebagian requirement dapat dipenuhi polyfill Composer, tetapi memasang extension native yang tersedia pada distribusi produksi tetap disarankan. Karena produksi memakai MySQL/MariaDB, PHP juga memerlukan PDO dan driver PDO MySQL. Setelah instalasi, verifikasi requirement aktual:

```bash
php -v
php -m
composer check-platform-reqs --no-dev
```

### Paket dan service

- PHP CLI dan PHP-FPM yang kompatibel;
- extension PHP di atas serta driver PDO MySQL;
- Composer 2;
- Git;
- Nginx;
- MySQL 8 atau MariaDB yang masih didukung;
- Certbot beserta integrasi Nginx yang tersedia untuk distribusi;
- `unzip`, `curl`, dan utilitas sistem dasar;
- Node.js dan npm hanya bila aset dibangun di VPS.

`package.json` menggunakan Vite 6, Tailwind CSS 4, dan Laravel Vite Plugin. Repository tidak menetapkan versi Node.js tertentu. Bila build dilakukan di VPS, pilih runtime Node.js yang didukung dependency terkunci dan buktikan dengan `npm ci` serta `npm run build`; jangan menebak versi hanya dari runbook ini.

PRD menyarankan memory limit PHP minimal 256 MB.

## 4. Keamanan server dasar

1. Buat user deployment non-root dan gunakan SSH key.
2. Uji login pada sesi SSH kedua sebelum menonaktifkan login password atau root. Kesalahan konfigurasi dapat mengunci seluruh akses VPS.
3. Setelah akses key terverifikasi, rekomendasikan menonaktifkan autentikasi password dan login root langsung sesuai kebijakan server.
4. Aktifkan firewall dan buka hanya port yang diperlukan:
   - SSH;
   - HTTP (`80`);
   - HTTPS (`443`).
5. Bila database berada pada VPS yang sama, bind database ke localhost dan jangan membuka port database ke internet.
6. Pastikan `.env`, backup, source, dan log tidak berada di bawah document root.
7. Gunakan permission minimum. Jangan pernah menggunakan `chmod 777`.
8. Produksi wajib memakai `APP_DEBUG=false`.
9. Setelah HTTPS aktif, gunakan `SESSION_SECURE_COOKIE=true`.
10. Terapkan update keamanan OS secara terencana dan ambil backup sebelum upgrade besar.

## 5. Struktur direktori

Struktur sederhana yang disarankan:

```text
/var/www/kupatbekasi/
├── current/                 # checkout source aktif
│   ├── app/
│   ├── bootstrap/
│   ├── public/              # document root Nginx
│   ├── storage/
│   ├── vendor/
│   └── .env                 # rahasia, tidak berada di public
└── backups/                 # opsional lokal, di luar public dan repository
```

- Owner source: user deployment, misalnya `deploy`.
- Group untuk direktori yang perlu ditulis PHP-FPM: `www-data` pada banyak sistem Debian/Ubuntu; verifikasi group aktual.
- Upload aplikasi: `/var/www/kupatbekasi/current/storage/app/public`.
- Document root: `/var/www/kupatbekasi/current/public`.
- Backup sebaiknya disalin ke penyimpanan terpisah dari VPS, bukan hanya disimpan pada disk yang sama.

Contoh pembuatan direktori awal:

```bash
sudo install -d -o deploy -g www-data /var/www/kupatbekasi/current
sudo install -d -o deploy -g deploy -m 0750 /var/www/kupatbekasi/backups
```

Sesuaikan user dan group sebelum menjalankan perintah.

## 6. Persiapan source

Clone menggunakan user deployment, lalu checkout commit atau tag yang telah disetujui:

```bash
cd /var/www/kupatbekasi
git clone <URL_REPOSITORY> current
cd current
git fetch --tags --prune
git checkout <TAG_ATAU_COMMIT_DEPLOYMENT>
git status --short
```

Jangan memindahkan dari workstation ke server:

- `.env` lokal;
- `database/database.sqlite` lokal;
- dump atau backup database;
- backup media;
- `node_modules`;
- credential, key, token, atau file rahasia lain.

Pasang dependency PHP produksi:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
```

### Opsi A: build aset di VPS

Gunakan bila Node.js/npm tersedia di server:

```bash
npm ci
npm run build
test -f public/build/manifest.json
```

`node_modules` tidak perlu disajikan oleh Nginx dan tidak perlu disimpan dalam artefak deployment final.

### Opsi B: build aset di lingkungan build tepercaya

Jalankan `npm ci` dan `npm run build` pada lingkungan build yang bersih, lalu kirim hanya isi `public/build` bersama source release. Pastikan artefak berasal dari commit yang sama. Jangan menjalankan `npm install` sembarang atau mengunggah `node_modules`.

## 7. Template environment produksi

Buat `.env` langsung di server dengan permission ketat. Contoh ini tidak berisi credential nyata dan sengaja tidak menampilkan `APP_KEY`:

```dotenv
APP_NAME=KUPATBekasi
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-resmi
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=nama_user
DB_PASSWORD=isi_secara-aman

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local
```

Catatan:

- Isi nilai rahasia melalui editor aman atau mekanisme secret provider. Jangan menaruhnya pada repository, tiket, atau command history.
- `APP_URL` harus memakai domain HTTPS final karena canonical URL dan URL media menggunakannya.
- Aplikasi saat ini tidak mempunyai job asynchronous wajib. `QUEUE_CONNECTION=sync` adalah pilihan paling kecil dan tidak memerlukan worker.
- Gunakan `QUEUE_CONNECTION=database` hanya jika fitur asynchronous benar-benar ditambahkan dan worker systemd/Supervisor juga disediakan.
- Database session dan cache memerlukan tabel hasil migration aplikasi.
- Jangan menyalin `.env.example` ke produksi tanpa meninjau setiap nilai development.

Untuk instalasi baru saja, buat application key setelah `.env` siap:

```bash
php artisan key:generate
```

Jangan mengganti application key pada produksi yang telah mempunyai data. Perubahan key dapat membuat data terenkripsi, cookie, dan session lama tidak dapat dibaca.

## 8. Konfigurasi database

Gunakan database dan user khusus aplikasi. Contoh SQL berikut memakai placeholder dan harus disesuaikan:

```sql
CREATE DATABASE nama_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nama_user'@'localhost' IDENTIFIED BY 'password-kuat-yang-dimasukkan-secara-aman';
GRANT ALL PRIVILEGES ON nama_database.* TO 'nama_user'@'localhost';
FLUSH PRIVILEGES;
```

Prinsip keamanan:

- berikan privilege hanya pada database KUPATBekasi;
- gunakan password unik dan kuat;
- jangan memakai user administratif database untuk aplikasi;
- gunakan koneksi localhost bila database berada pada VPS yang sama;
- jangan mengekspos port MySQL/MariaDB ke internet;
- ambil backup sebelum setiap migration produksi.

## 9. Permission aplikasi

Source cukup dapat dibaca web server. Hanya `storage` dan `bootstrap/cache` yang perlu ditulis PHP-FPM:

```bash
cd /var/www/kupatbekasi/current
sudo chown -R deploy:www-data .
sudo find . -type d -exec chmod 0755 {} \;
sudo find . -type f -exec chmod 0644 {} \;
sudo chown -R deploy:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 0775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 0664 {} \;
sudo chmod 0640 .env
```

Verifikasi user PHP-FPM dan group web server pada VPS. Bila model permission hosting berbeda, sesuaikan tanpa memberikan write access global. Jangan menggunakan `chmod 777`.

## 10. Urutan deployment

Ambil backup sebelum perubahan source, database, atau media. Untuk deployment awal atau pembaruan terencana:

```bash
cd /var/www/kupatbekasi/current
git fetch --tags --prune
git checkout <TAG_ATAU_COMMIT_DEPLOYMENT>
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Bangun aset dengan salah satu strategi pada bagian sebelumnya. Setelah permission benar, verifikasi:

```bash
test -f public/build/manifest.json
test -L public/storage
php artisan about
php artisan route:list
```

Larangan produksi:

- jangan menjalankan `php artisan migrate:fresh`;
- jangan menjalankan `KupatBekasiDemoSeeder`;
- jangan menjalankan `php artisan db:seed` tanpa keputusan eksplisit;
- jangan menjalankan `php artisan kupat:purge-demo-data` tanpa backup dan keputusan eksplisit;
- jangan mengganti `APP_KEY` pada aplikasi yang telah berisi data;
- jangan menggunakan `chmod 777`.

## 11. Konfigurasi Nginx

Contoh server block HTTP awal berikut harus disesuaikan. Placeholder `<SOCKET_PHP_FPM>` harus diganti dengan socket PHP-FPM yang benar-benar tersedia, misalnya hasil pemeriksaan service dan konfigurasi pool; jangan menebak versinya.

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name domain-resmi;

    root /var/www/kupatbekasi/current/public;
    index index.php;

    client_max_body_size 2m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:<SOCKET_PHP_FPM>;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* ^/(?:\.env|composer\.(?:json|lock)|package(?:-lock)?\.json|artisan)$ {
        deny all;
    }
}
```

`client_max_body_size 2m` selaras dengan batas umum upload media aplikasi sebesar 2 MB. Favicon dibatasi lebih kecil oleh aplikasi. Bila overhead multipart pada lingkungan nyata menyebabkan request ditolak sebelum validasi aplikasi, naikkan sedikit secara terukur tanpa mengubah batas validasi aplikasi.

Aktifkan dan uji konfigurasi:

```bash
sudo ln -s /etc/nginx/sites-available/kupatbekasi /etc/nginx/sites-enabled/kupatbekasi
sudo nginx -t
sudo systemctl reload nginx
```

Pastikan konfigurasi default yang konflik telah ditangani sesuai kebijakan server. Setelah Certbot mengaktifkan HTTPS, periksa kembali server block hasil perubahan otomatis.

## 12. SSL Let's Encrypt

Instal Certbot dan plugin Nginx menggunakan paket resmi distribusi, lalu terbitkan sertifikat:

```bash
sudo certbot --nginx -d domain-resmi
```

Pilih redirect HTTP ke HTTPS bila ditawarkan. Verifikasi konfigurasi dan renewal:

```bash
sudo nginx -t
sudo systemctl reload nginx
sudo certbot renew --dry-run
```

Setelah HTTPS aktif:

1. pastikan `APP_URL=https://domain-resmi`;
2. pastikan `SESSION_SECURE_COOKIE=true`;
3. jalankan kembali `php artisan optimize` setelah perubahan environment;
4. verifikasi canonical URL memakai HTTPS;
5. verifikasi URL logo, favicon, banner, produk, dan media galeri memakai HTTPS;
6. verifikasi login serta logout `/admin` melalui HTTPS;
7. periksa mixed content pada browser.

## 13. Queue, scheduler, dan proses background

Audit repository saat runbook dibuat menemukan:

- tidak ada job aplikasi yang mengimplementasikan antrean;
- tidak ada pemanggilan dispatch job aplikasi;
- tidak ada Laravel scheduler aplikasi;
- tidak ada WebSocket;
- tidak ada proses background wajib.

Karena itu, queue worker dan cron scheduler belum wajib. Gunakan `QUEUE_CONNECTION=sync` agar tidak ada job yang tertahan tanpa worker.

### Scheduler opsional

Bila scheduler ditambahkan kemudian, pasang satu cron entry untuk user deployment:

```cron
* * * * * cd /var/www/kupatbekasi/current && php artisan schedule:run >> /dev/null 2>&1
```

Jangan menambahkan cron hanya karena tersedia; aktifkan setelah ada scheduled task nyata dan telah diuji.

### Worker queue opsional

Bila fitur async nyata ditambahkan, ubah koneksi queue, siapkan worker menggunakan systemd atau Supervisor, serta tetapkan restart, retry, timeout, dan monitoring sesuai job tersebut. Contoh konsep Supervisor:

```ini
[program:kupatbekasi-worker]
command=php /var/www/kupatbekasi/current/artisan queue:work --sleep=3 --tries=3 --timeout=90
directory=/var/www/kupatbekasi/current
user=deploy
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/kupatbekasi/current/storage/logs/worker.log
```

Contoh ini opsional dan bukan requirement aplikasi saat ini.

## 14. Logging dan monitoring

Konfigurasi produksi yang disarankan untuk MVP:

```dotenv
LOG_CHANNEL=daily
LOG_LEVEL=warning
```

Pastikan PHP-FPM dapat menulis ke `storage/logs`. Channel `daily` memberikan retensi harian Laravel; selaraskan dengan kapasitas disk dan kebijakan retensi server.

Pantau minimal:

- `storage/logs/laravel-*.log` atau file log sesuai channel aktif;
- Nginx access log;
- Nginx error log;
- PHP-FPM log;
- penggunaan disk, terutama `storage/app/public`, log, dan backup;
- status Nginx, PHP-FPM, database, dan renewal Certbot;
- respons endpoint `/up` bila dipakai untuk health check.

Jangan mencatat `.env`, password, key, token, isi cookie, atau credential pada log maupun laporan insiden. Konfigurasikan rotasi log sistem untuk Nginx dan PHP-FPM sesuai distribusi.

## 15. Backup

Backup minimum mencakup:

- database MySQL/MariaDB;
- `storage/app/public`;
- `.env`, disimpan terenkripsi atau dengan akses terbatas di luar web root;
- referensi commit/tag source yang sedang aktif.

Jangan menyimpan backup di repository atau folder `public`. Salin backup ke lokasi terpisah dari VPS dan tetapkan retensi sesuai kebijakan organisasi.

Untuk menghindari password database tersimpan di command history, gunakan login-path, option file berpermission ketat, atau prompt interaktif. Contoh dengan login-path yang telah dikonfigurasi aman:

```bash
mysqldump --login-path=kupatbekasi --single-transaction --routines --triggers nama_database \
    > /var/www/kupatbekasi/backups/database-YYYYMMDD-HHMMSS.sql
```

Backup media:

```bash
tar -C /var/www/kupatbekasi/current/storage/app \
    -czf /var/www/kupatbekasi/backups/public-media-YYYYMMDD-HHMMSS.tar.gz public
```

Uji proses restore secara berkala. Backup yang belum pernah diuji restore belum dapat dianggap tervalidasi.

## 16. Smoke test produksi

Setelah deployment, periksa melalui HTTPS:

- [ ] beranda tampil tanpa error;
- [ ] katalog produk tampil;
- [ ] pencarian produk dan mitra bekerja;
- [ ] filter kategori dan pagination bekerja;
- [ ] detail produk dapat dibuka melalui slug;
- [ ] foto utama dan galeri tampil;
- [ ] tombol WhatsApp menghasilkan tujuan dan pesan yang benar;
- [ ] daftar serta profil mitra tampil;
- [ ] halaman Tentang, Kontak, dan Kebijakan Privasi dapat dibuka;
- [ ] `/admin/login` dapat dibuka;
- [ ] hanya administrator yang dapat masuk ke panel;
- [ ] CRUD Mitra bekerja;
- [ ] CRUD Produk dan relasinya bekerja;
- [ ] upload serta penghapusan media bekerja;
- [ ] logo dan favicon tampil;
- [ ] seluruh URL publik, canonical, media, dan admin menggunakan HTTPS;
- [ ] halaman yang tidak ada menghasilkan 404;
- [ ] tidak ada mixed content;
- [ ] tidak ada error baru pada log Laravel, Nginx, atau PHP-FPM;
- [ ] `APP_DEBUG` tidak menampilkan detail error.

Lakukan smoke test dengan data yang aman dan hapus record pengujian melalui UI bila memang dibuat khusus untuk verifikasi.

## 17. Rollback

Rollback harus direncanakan sebelum deployment. Urutan dasar:

```bash
cd /var/www/kupatbekasi/current
php artisan down
```

Kemudian:

1. restore source ke commit/tag release sebelumnya;
2. restore database dari backup bila migration atau data berubah;
3. restore `storage/app/public` bila media berubah atau terhapus;
4. jalankan `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction` bila dependency release berbeda;
5. pastikan artefak `public/build` sesuai release yang dipulihkan;
6. jalankan `php artisan optimize`;
7. verifikasi aplikasi melalui akses terbatas atau pemeriksaan lokal server;
8. matikan maintenance mode setelah verifikasi:

```bash
php artisan up
```

Rollback source tanpa rollback database tidak selalu aman. Migration yang menghapus atau mengubah struktur/data dapat membuat source lama tidak kompatibel. Jangan menjalankan perintah rollback migration otomatis tanpa meninjau migration dan backup terkait.

## 18. Checklist pascadeployment

- [ ] commit atau tag deployment dicatat;
- [ ] domain dan document root mengarah ke `current/public`;
- [ ] HTTPS aktif dan renewal tervalidasi;
- [ ] `APP_ENV=production`;
- [ ] `APP_DEBUG=false`;
- [ ] `APP_URL` memakai HTTPS;
- [ ] session cookie aman pada HTTPS;
- [ ] admin hanya dapat diakses administrator;
- [ ] migration berhasil;
- [ ] storage link aktif;
- [ ] config, route, event, dan view cache aktif melalui optimize;
- [ ] media tampil dan upload teruji;
- [ ] queue worker tidak dipasang tanpa kebutuhan;
- [ ] log produksi bersih dari error kritis dan secret;
- [ ] backup database, media, dan environment tersedia;
- [ ] backup akhir pascadeployment disalin ke lokasi terpisah;
- [ ] smoke test selesai dan hasilnya dicatat.

## 19. Perbedaan VPS dan cPanel

Pada VPS tanpa panel, tim bertanggung jawab langsung atas OS, patch keamanan, user dan SSH, firewall, Nginx, PHP-FPM, database, SSL, service background, permission, monitoring, rotasi log, serta backup. cPanel mengelola sebagian komponen tersebut melalui antarmuka panel dan kebijakan hosting.

Runbook ini ditujukan untuk VPS Linux tanpa panel. Instruksi deployment cPanel pada PRD tetap menjadi baseline terpisah dan tidak boleh diterapkan mentah-mentah pada VPS tanpa memeriksa perbedaan document root, service, permission, dan akses server.
