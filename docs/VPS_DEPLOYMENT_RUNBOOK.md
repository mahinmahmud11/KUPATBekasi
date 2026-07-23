# KUPATBekasi VPS Deployment Runbook

## 1. Gambaran Umum

Dokumen ini adalah panduan resmi (runbook) untuk melakukan _deployment_ produksi aplikasi KUPATBekasi ke lingkungan Virtual Private Server (VPS). 

**Asumsi Lingkungan:**
- Menggunakan VPS baru bersistem operasi **Ubuntu Server 24.04 LTS**.
- Pengguna yang mengeksekusi panduan ini memiliki akses `sudo`.
- _Document root_ web server **wajib** diarahkan secara absolut ke direktori `/var/www/kupatbekasi/public`.

## 2. Persiapan Server

Perbarui indeks paket sistem dan pastikan semua komponen sistem mutakhir:

```bash
sudo apt update && sudo apt upgrade -y
```

Instal dependensi perangkat lunak dasar, web server, MariaDB, Git, unzip, dan curl:

```bash
sudo apt install -y nginx mariadb-server git unzip curl
```

Ubuntu 24.04 menggunakan PHP 8.3 secara bawaan dari repository Ubuntu. Instal PHP 8.3 dan ekstensi Laravel:

```bash
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-bcmath php8.3-intl
```

Instal Composer secara resmi dengan prosedur verifikasi signature/checksum yang berhenti jika tidak cocok:

```bash
EXPECTED_CHECKSUM="$(curl -fsSL https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    echo "Checksum Composer tidak cocok." >&2
    rm -f composer-setup.php
    exit 1
fi

php composer-setup.php --filename=composer
rm -f composer-setup.php
sudo mv composer /usr/local/bin/composer
sudo chmod 755 /usr/local/bin/composer
```
> **Catatan:** Composer dependency tetap dijalankan sebagai `deploy`, bukan `root` atau `www-data`.

Instal Node.js 22 (LTS):

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

Lakukan verifikasi instalasi paket di atas:
```bash
php -v
composer -V
node -v
npm -v
nginx -v
mysql -V
```

Konfigurasi Batas Upload PHP-FPM (aplikasi KUPATBekasi dibatasi maksimal 2 MB):
Edit file `/etc/php/8.3/fpm/php.ini` dan perbarui nilai parameter berikut:
```ini
upload_max_filesize = 3M
post_max_size = 4M
```
Kemudian cek konfigurasi PHP dan muat ulang:
```bash
sudo php-fpm8.3 -t
sudo systemctl reload php8.3-fpm
```

## 3. Akun Deployment

Jangan menggunakan pengguna `root` untuk mengeksekusi operasional deployment aplikasi. Buatlah akun khusus:

```bash
sudo adduser deploy
sudo usermod -aG www-data deploy
```
Pastikan selanjutnya Anda *login* sebagai pengguna `deploy` untuk mengeksekusi langkah-langkah selanjutnya.

## 4. Pembuatan Database Production

Amankan instalasi database terlebih dahulu:
```bash
sudo mysql_secure_installation
```

Masuk ke mode perintah MariaDB:
```bash
sudo mysql
```

Buat basis data dan pengguna secara spesifik. Jangan menggunakan pengguna `root` database untuk aplikasi KUPATBekasi. Implementasikan hak akses minimum:

```sql
CREATE DATABASE kupatbekasi_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kupatbekasi_user'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON kupatbekasi_prod.* TO 'kupatbekasi_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 5. Pengambilan Source Code

Gunakan Git untuk mengambil kode aplikasi (jalankan sebagai user `deploy`):

```bash
cd /var/www
sudo mkdir kupatbekasi
sudo chown deploy:www-data kupatbekasi
cd kupatbekasi
git clone https://github.com/mahinmahmud11/KUPATBekasi.git .
```
> **Penting**: Pastikan direktori *source code* wajib dimiliki oleh `deploy:www-data`. Jangan pernah menjadikan seluruh repository sepenuhnya milik `www-data`.

Pastikan Anda berada di _branch_ utama dan memverifikasi posisi komit:
```bash
git checkout main
git fetch origin
git reset --hard origin/main
git log -1 --oneline --decorate
```

Unduh pustaka Composer (khusus produksi):
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

Unduh dan susun (*build*) aset _frontend_ Vite:
```bash
npm ci
npm run build
```

## 6. Konfigurasi Environment

Salin _template environment_:
```bash
cp .env.example .env
sudo chown deploy:www-data .env
sudo chmod 640 .env
```
> **Penjelasan Hak Akses**: `deploy` dapat mengedit konfigurasi, `www-data` dapat membaca, dan pengguna lain (serta grup lain di luar `www-data`) tidak memiliki akses sama sekali.

Sunting file `.env` dan pastikan konfigurasi penting disesuaikan. Jangan menampilkan isinya sembarangan.
```ini
APP_NAME=KUPATBekasi
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kupatbekasi_prod
DB_USERNAME=kupatbekasi_user
DB_PASSWORD=GANTI_DENGAN_PASSWORD_KUAT

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

> **PERINGATAN**: Jalankan perintah `key:generate` HANYA pada instalasi pertama. Jangan menjalankannya pada *update deployment*.
```bash
php artisan key:generate
```

## 7. Permission dan Storage

Web server (`www-data`) HANYA boleh menulis ke folder `storage` dan `bootstrap/cache`. Atur `setgid` dan kepemilikan yang aman. Jangan menjalankan chmod 644/755 rekursif untuk seluruh repository.

```bash
sudo chown -R deploy:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 0664 {} \;
```

Bangun _symbolic link_ penyimpanan aset (dijalankan oleh `deploy`):
```bash
php artisan storage:link
ls -la public/storage
```

## 8. Database Migration

Migrasikan skema basis data ke server produksi.
Jangan memakai `--seed` atau `db:seed`.

```bash
php artisan migrate --force
```

Memeriksa status migration:
```bash
php artisan migrate:status
```
> **Catatan Seeder**: DatabaseSeeder secara *default* akan menjalankan `KupatBekasiDemoSeeder`. Jangan menjalankan seeder demo di production. Jangan menjalankan *command* `kupat:purge-demo-data` kecuali sudah ada *backup* dan benar-benar diperlukan.

## 9. Pembuatan Administrator

Agar Anda memiliki hak manajemen di Dasbor Panel Filament (dijalankan oleh `deploy`):

```bash
php artisan kupat:make-admin
```
Command administrator resmi KUPATBekasi memastikan atribut akun memiliki `is_admin=true`. Jangan menampilkan *password* admin secara kasatmata, lalu lakukan verifikasi *login* melalui `/admin`. Jangan menggunakan utilitas `php artisan make:filament-user`.

## 10. Konfigurasi Nginx

Buat *server block* baru:
```bash
sudo nano /etc/nginx/sites-available/kupatbekasi
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name example.com;
    root /var/www/kupatbekasi/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    client_max_body_size 3M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include fastcgi.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_hide_header X-Powered-By;
    }

    # Blok file-file sensitif agar tidak terekspos langsung
    location ~* ^/(?:artisan|composer\.(?:json|lock)|package(?:-lock)?\.json)$ {
        deny all;
    }

    # Izinkan certbot
    location ^~ /.well-known/acme-challenge/ {
        allow all;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan profil Nginx, uji konfigurasinya dengan `nginx -t`, baru muat ulang:
```bash
sudo ln -s /etc/nginx/sites-available/kupatbekasi /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 11. HTTPS

Pasang SSL Let's Encrypt dengan Certbot. Jangan mengaktifkan HTTPS manual sebelum Certbot dijalankan.
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d example.com
```

Lakukan tes pembaruan otomatis sertifikat untuk memverifikasi _auto-renew_ dan _redirect HTTP ke HTTPS_:
```bash
sudo certbot renew --dry-run
```

## 12. Optimasi Production

Perakitan *cache* rute, pengaturan, dan tampilan:
```bash
php artisan optimize
```
> Jelaskan bahwa `php artisan optimize:clear` terbatas digunakan untuk *troubleshooting* atau sekadar membersihkan _cache_ usang sebelum me-*rebuild* cache baru (`optimize`). Jangan menjalankan _composer install_ tanpa opsi `--no-dev` di server *production*.

## 13. Queue dan Scheduler

**Opsional—hanya ketika aplikasi mulai menggunakan queued jobs**

> **PENTING**: Jangan instal atau aktifkan Supervisor sekarang. Langkah ini hanya dilakukan ketika *queued jobs* benar-benar mulai digunakan pada tahap pengembangan berikutnya. Setelah instalasi barulah konfigurasi *worker* dan `supervisorctl` digunakan. Pekerja _Supervisor_ belum diwajibkan (catatan: Worker tidak boleh berjalan sebagai _root_). Begitu juga `routes/console.php` saat ini tidak memiliki _schedule_ sehingga *cron* belum perlu dipasang. Contoh konfigurasi ini murni untuk opsional masa depan.

Instalasi awal (opsional masa depan):
```bash
sudo apt install -y supervisor
```

Contoh `/etc/supervisor/conf.d/kupatbekasi-worker.conf`:
```ini
[program:kupatbekasi-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/kupatbekasi/artisan queue:work --sleep=3 --tries=3 --timeout=90
directory=/var/www/kupatbekasi
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/kupatbekasi/storage/logs/worker.log
stopwaitsecs=3600
```
Sertakan perintah aktivasi (*Tegaskan: JANGAN DIJALANKAN SEKARANG*):
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Contoh opsional Scheduler masa depan (jangan dipasang bila belum perlu):
```cron
* * * * * cd /var/www/kupatbekasi && php artisan schedule:run >> /dev/null 2>&1
```

## 14. Persiapan Backup Server

Sebelum _backup_ pertama, siapkan direktori backup bagi pengguna _deploy_:
```bash
sudo install -d -o deploy -g deploy -m 700 /var/backups/kupatbekasi
```

Saat hendak _backup_, gunakan _timestamp_ yang mencakup tanggal dan waktu pasti, serta amankan _permission_:
```bash
BACKUP_TIMESTAMP="$(date +%Y%m%d_%H%M%S)"

# Backup database
mysqldump -u kupatbekasi_user -p kupatbekasi_prod \
  > "/var/backups/kupatbekasi/database_${BACKUP_TIMESTAMP}.sql"

# Backup .env (Wajib mode 600 di luar folder public, tidak perlu dibaca PHP-FPM)
install -m 600 .env \
  "/var/backups/kupatbekasi/env_${BACKUP_TIMESTAMP}"

# Backup uploads public
tar -czf "/var/backups/kupatbekasi/storage_${BACKUP_TIMESTAMP}.tar.gz" \
  storage/app/public
```
Verifikasi keberadaan *backup* tanpa membuka isinya:
```bash
ls -lh /var/backups/kupatbekasi
```

## 15. Prosedur Update Deployment

Seluruh rangkaian prosedur update harus tersedia dalam SATU blok kode bash utuh yang dapat disalin dan dijalankan dalam satu sesi shell.

```bash
cd /var/www/kupatbekasi

set -Eeuo pipefail

if [ "$(id -u)" -eq 0 ]; then
    echo "Deployment dihentikan: jangan jalankan sebagai root." >&2
    exit 1
fi
if [ "$(whoami)" != "deploy" ]; then
    echo "Deployment dihentikan: user aktif harus deploy." >&2
    exit 1
fi

test "$(git branch --show-current)" = "main" || {
    echo "Deployment dihentikan: branch bukan main." >&2
    exit 1
}

test -z "$(git status --porcelain)" || {
    echo "Deployment dihentikan: working tree tidak bersih." >&2
    exit 1
}

OLD_COMMIT="$(git rev-parse HEAD)"
echo "Commit sebelum update: $OLD_COMMIT"
BACKUP_TIMESTAMP="$(date +%Y%m%d_%H%M%S)"

sudo install -d -o deploy -g deploy -m 700 /var/backups/kupatbekasi

mysqldump -u kupatbekasi_user -p kupatbekasi_prod > "/var/backups/kupatbekasi/database_${BACKUP_TIMESTAMP}.sql"
install -m 600 .env "/var/backups/kupatbekasi/env_${BACKUP_TIMESTAMP}"
tar -czf "/var/backups/kupatbekasi/storage_${BACKUP_TIMESTAMP}.tar.gz" storage/app/public

test -s "/var/backups/kupatbekasi/database_${BACKUP_TIMESTAMP}.sql"
test -s "/var/backups/kupatbekasi/env_${BACKUP_TIMESTAMP}"
test -s "/var/backups/kupatbekasi/storage_${BACKUP_TIMESTAMP}.tar.gz"

APP_IS_DOWN=0

restore_application() {
    if [ "$APP_IS_DOWN" -eq 1 ]; then
        php artisan up || true
    fi
}

trap restore_application EXIT

php artisan down
APP_IS_DOWN=1

git fetch origin
git checkout main
git reset --hard origin/main
git log -1 --oneline --decorate

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
test -f public/build/manifest.json
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

php artisan about
php artisan migrate:status
test -L public/storage
test -f public/build/manifest.json

php artisan up
APP_IS_DOWN=0

HOME_STATUS="$(curl -sS -o /dev/null -w '%{http_code}' https://example.com)"

if [ "$HOME_STATUS" != "200" ]; then
    echo "Health check gagal: beranda mengembalikan HTTP $HOME_STATUS." >&2
    exit 1
fi

ADMIN_RESULT="$(curl -sS -o /dev/null -w '%{http_code} %{redirect_url}' https://example.com/admin)"
ADMIN_STATUS="${ADMIN_RESULT%% *}"
ADMIN_REDIRECT="${ADMIN_RESULT#* }"

case "$ADMIN_STATUS" in
    200)
        ;;
    302)
        case "$ADMIN_REDIRECT" in
            https://example.com/*)
                ;;
            *)
                echo "Health check gagal: redirect admin tidak menuju domain HTTPS yang benar: $ADMIN_REDIRECT" >&2
                exit 1
                ;;
        esac
        ;;
    *)
        echo "Health check gagal: /admin mengembalikan HTTP $ADMIN_STATUS." >&2
        exit 1
        ;;
esac

trap - EXIT
echo "Deployment berhasil."
```

**Penjelasan Prosedur Update:**
- seluruh blok wajib dijalankan sekaligus dalam satu shell;
- jangan menyalin hanya sebagian blok;
- trap otomatis menjalankan `php artisan up` bila terjadi kegagalan setelah maintenance mode aktif;
- beranda wajib HTTP 200;
- `/admin` hanya diterima bila HTTP 200 atau 302;
- redirect 302 wajib menuju URL HTTPS pada example.com;
- HTTP 4xx, 5xx, redirect ke domain lain, atau kegagalan koneksi menghentikan deployment;
- health check interaktif lengkap tetap mengikuti bagian Health Check Pascadeployment;
- bila deployment gagal, `OLD_COMMIT` dan file backup digunakan untuk prosedur rollback;
- `queue:restart` tidak dimasukkan karena worker belum aktif.

## 16. Health Check Pascadeployment

Lakukan observasi fungsional, dan sertakan _command read-only_ untuk mengumpulkan metrik kelayakan:

```bash
curl -I https://example.com
curl -I https://example.com/admin
php artisan about
php artisan migrate:status
php artisan route:list
test -L public/storage && ls -la public/storage
ls -ld storage bootstrap/cache
sudo systemctl status nginx --no-pager
sudo systemctl status php8.3-fpm --no-pager
sudo systemctl status mariadb --no-pager
tail -n 100 storage/logs/laravel.log
```
- **Halaman Publik dan Admin**: Secara normal, beranda (contoh `curl -I https://example.com`) akan memberikan respons HTTP `200`. Rute `/admin` dapat merespons HTTP `200` atau menghasilkan *redirect* `302` yang menuju ke halaman login, selama *redirect* tersebut tetap mengacu pada domain HTTPS yang benar. Jika merespons dengan HTTP `500`, `502`, atau *redirect* berulang secara tidak wajar, maka aplikasi tidak dapat diakses (gagal).
- Lakukan login ke `/admin` sebagai administrator.
- Lakukan CRUD data uji dengan aman (misal daftar mitra, detail produk baru, uji ukuran _upload_ file hingga rasio batas aman maksimal **2 MB**).
- **Hapus Data Uji**: Setelah pengujian interaktif kelar, pangkas bersih kembali rintisan uji coba tersebut.
- Jangan *pernah* mencetak/membeberkan variabel sandi dari `.env`. Fungsi `tail` digunakan hanya untuk mendeteksi *error* secara cepat, dan hasil output yang mengandung eksposur data sensitif sama sekali tidak boleh dibagikan.

## 17. Rollback Aman

Jika instalasi terindikasi menabrak kegagalan kompatibilitas fatal, kembali secara presisi ke komit terekam:

1. Letakkan sistem dalam status pemeliharaan _maintenance mode_.
2. Lakukan satu _backup_ kilat terkait kondisi *error* terkini guna penyelidikan teknis.
3. Mundurkan referensi Git ke *commit hash* yang valid:
   ```bash
   git checkout --detach "$OLD_COMMIT"
   ```
   > **Penjelasan**: `OLD_COMMIT` merujuk ke parameter valid berbentuk representasi literal *hash commit* yang benar-benar tercatat oleh _git rev-parse HEAD_ sesaat sebelum prosedur _update_.
4. Pasang perlengkapan _dependensi_ persis dengan jejak kode lama:
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   npm ci
   npm run build
   php artisan optimize:clear
   php artisan optimize
   ```
5. _Database_ hanya direstore dari riwayat _backup_ bila _migration_ yang baru rilis dipastikan sangat tidak kompatibel secara struktur.
6. Jangan menggunakan perintah utilitas `migrate:rollback` tanpa analisis riwayat tabel secara mendalam!
7. Buka pintu publik server (*php artisan up*) dan evaluasi _Health Check_.
8. Setelah stabil, tentukan apakah server akan tetap berstatus _detached_ di versi perlindungan tersebut, atau ditautkan kembali ke posisi *branch main* sesudah _bug-fix deployment_ rilis.

## 18. Troubleshooting Terpadu

Untuk setiap kasus, sertakan prosedur pemeriksaan aman:

- **HTTP 500 Error**: Pantau log aktivitas, identifikasi layanan sistem, dan uji eksistensi parameter esensial tanpa menyebarluaskan nilai rahasia (contoh keberadaan `APP_KEY` melalui `php artisan about`).
- **403 Gagal Login Admin**: Pantau rekaman *log*, dan eksekusi instrumen kustom `kupat:make-admin` khusus untuk mendefinisikan ulang legitimasi hanya pada akun operasional yang sah.
- **Permission Denied**: Lakukan deteksi presisi hierarki atribut: manfaatkan perintah `namei -l` terhadap target path berkas unggahan, serta `ls -ld` atas titik utama kepemilikan direktori pengampu.
- **Gambar/Symlink Bisu (404)**: Amati status koneksi *symlink* dengan merangkai perintah `test -L public/storage`, kemudian ungkap validitas target lintasan via fitur *readlink*.
- **Database Connection Refused**: Cek operasional keseluruhan dengan `systemctl status mariadb` atau selidiki indikasi gangguan paramater secara taktis lewat `php artisan migrate:status`.
- **Migration Gagal**: Waspadai benturan tipe relasi *(collision)*, utamakan pencadangan _backup_ dan pemeriksaan *log*, lalu perbaiki kerusakan secara manual. Jangan melakukan prosedur *rollback* otomatis yang destruktif.
- **Vite Manifest Not Found**: Kompilasi statis kosong. Pantau ketersediaan manifest: `test -f public/build/manifest.json`.
- **Config Cache Mengendap**: Tangani jejak sistem yang basi dengan utilitas regenerasi *cache*: `php artisan optimize:clear` yang langsung diikat rilis bersih melalui `php artisan optimize`.
- **Nginx 502 Bad Gateway**: Jembatan lalu-lintas lumpuh. Verifikasi `systemctl status php8.3-fpm` dan selidiki respons pada ekstensi alur _socket_-nya.
- **Upload Nginx/PHP Putus**: Batas penanganan ukuran dilanggar. Evaluasi konsistensi paramater di spesifikasi batas server Nginx (`client_max_body_size`) dan spesifikasi PHP (`upload_max_filesize`).
- **SSL Error / Sertifikat**: Penolakan interkoneksi sertifikat otentik. Pelajari parameter status _Let's Encrypt_ via perintah pembacaan `certbot certificates` serta `renew --dry-run`.
- **Redirect Loop HTTPS**: Siklus _port proxy_ yang bertolak belakang. Validasi keakuratan standar URL via deklarasi mutlak `APP_URL` HTTPS dan sinkronisasi blok setelan _proxy_.
- **Terjebak Maintenance Mode**: Aplikasi tidak dapat diakses karena terkunci. Paksa lepas penguncian status via `php artisan up`.

## 19. Checklist

### I. Sebelum Deployment Pertama
- [ ] Resolusi DNS Domain terkunci aman mengarah ke titik IP publik peladen.
- [ ] Akses _Database_ mutlak dijalankan secara non-root.
- [ ] Pengguna `deploy` telah tercipta serta akses kepemilikan repositori diinisiasi cermat.
- [ ] Direktori _backup_ dikonfigurasi secara terbatas di bawah standar penguncian mode *700*.

### II. Saat Deployment Pertama
- [ ] _Branch_ operasional diverifikasi persis berada pada ranting `main` (*commit tracking* selaras).
- [ ] Konfigurasi `.env` ditata kokoh: `APP_ENV production`, `APP_DEBUG false`, serta parameter wajib dilindungi oleh _permission_ kepemilikan `640`. Akses `APP_URL` dipaksa ke jalur enkripsi aman HTTPS.
- [ ] Penuntasan 100% proses penarikan pustaka `composer install --no-dev` & utilitas rakitan UI `npm build Vite`.

### III. Setelah Deployment Pertama
- [ ] Tautan aset visual termuat: indikasi jalur terverifikasi pada *storage link*.
- [ ] Proses hulu _migration_ selesai beroperasi tanpa kehadiran *seeder* palsu.
- [ ] Profil otorisasi _admin_ tercipta secara sah dan aman via eksekusi konsol KUPATBekasi.
- [ ] Infrastruktur web server tangguh: Sinkronisasi harmonis antara Nginx, sertifikat SSL HTTPS (Certbot), instrumen penyegaran _optimize_, serta status murni _Health Check_.

### IV. Sebelum Update
- [ ] Sistem versi Git bebas dari gangguan rekayasa lokal (*working tree* bersih).
- [ ] File sakral konfigurasi `.env`, _database_, beserta folder _uploads_ berhasil diamankan sempurna pada lokasi aman wadah _backup_.
- [ ] Status interupsi *trap* pemeliharaan _maintenance mode_ dipastikan berkerja melingkupi operasional CLI penyebaran pembaruan.

### V. Setelah Update
- [ ] Eksekusi rekayasa logika termutakhirkan sepenuhnya melalui *optimize*.
- [ ] Segel pelindung rilis _maintenance mode_ dapat tereksekusi lepas dengan aman.
- [ ] Pengujian diagnosis kualitas *Health Check* dan audit catatan _Log_ membuahkan hasil stabil 100%. Verifikasi _login_ operasional Admin lulus, serta seluruh kapabilitas pengujian alur penambahan dan manipulasi data *(CRUD)* sukses terlaksana dengan *permission* dan otorisasi berkas yang terjaga utuh.
