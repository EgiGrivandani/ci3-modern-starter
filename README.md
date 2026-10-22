# CodeIgniter 3 Modern Starter Template (PHP 8.5.12 ≤ versi < 8.6.0)

Starter template **CodeIgniter 3 (v3.4.5 - PocketARC Edition)** yang telah dimodifikasi dengan struktur, fitur, keamanan modern bergaya **Laravel**, serta fondasi **Engineering Standard berbasis DDIA (*Designing Data-Intensive Applications*) & Clean Architecture**. Template ini dirancang dengan prinsip **Reliable, Scalable, Maintainable, Clean, dan Secure**.

> **Versi PHP wajib: mulai 8.5.12, hanya dalam seri 8.5.** Batasnya adalah `>=8.5.12` dan `<8.6.0`, sesuai `~8.5.12` di `composer.json`. Persyaratan ini berlaku untuk PHP CLI dan web server, baik di lokal maupun production.

> **📘 Dokumen Standar Programming**: Baca panduan lengkap 6 prinsip penulisan kode, aturan arsitektur 3-Layer, penamaan kode, transaksi database, dan standar API di **[CODING_STANDARD.md](CODING_STANDARD.md)**.

> **Kerja tim & proyek turunan**: Baca [CONTRIBUTING.md](CONTRIBUTING.md) untuk setup anggota tim, penggunaan template, formatter (`composer format` / `composer format:check`), alur branch/PR, dan aturan migrasi. Workflow GitHub Actions ikut tersedia pada setiap repository yang dibuat dari template ini.

---

## Daftar Isi
1. [Apa Saja yang Dimodifikasi dari CI3 Standar?](#1-apa-saja-yang-dimodifikasi-dari-ci3-standar)
2. [Spesifikasi & Tech Stack](#2-spesifikasi--tech-stack)
3. [Struktur Direktori](#3-struktur-direktori)
4. [Cara Setup untuk Project Baru](#4-cara-setup-untuk-project-baru)
5. [Panduan Cara Pakai (Cheat Sheet)](#5-panduan-cara-pakai-cheat-sheet)
   - [A. Konfigurasi `.env` & Fungsi `env()`](#a-konfigurasi-env--fungsi-env)
   - [B. Membuat Controller & Master Layout (`MY_Controller`)](#b-membuat-controller--master-layout-my_controller)
   - [C. Standar REST API, Rate Limit, Validasi & Exception Handling](#c-standar-rest-api-rate-limit-validasi--exception-handling)
   - [D. Model CRUD, Transaction, Locking & Soft Delete (`MY_Model`)](#d-model-crud-transaction-locking--soft-delete-my_model)
   - [E. Database Migration via CLI (`composer migrate`)](#e-database-migration-via-cli-composer-migrate)
   - [F. Manajemen File Upload (`storage/private` vs `public/uploads`)](#f-manajemen-file-upload-storageprivate-vs-publicuploads)
   - [G. Membuat Class PSR-4 Modern (`App\Services\...`)](#g-membuat-class-psr-4-modern-appservices)
   - [H. Helper Global (`e()`, `csrf_field()`, `asset()`, `client_ip()`, `request_id()`, `log_context()`, `dd()`)](#h-helper-global-e-csrf_field-asset-client_ip-request_id-log_context-dd)
   - [I. Menjalankan Unit Test (PHPUnit)](#i-menjalankan-unit-test-phpunit)
6. [Standar Keamanan (Security Hardening)](#6-standar-keamanan-security-hardening)

---

## 1. Apa Saja yang Dimodifikasi dari CI3 Standar?

| Fitur | CI3 Standar (v3.1.13) | Template CI3 Modern Ini |
| :--- | :--- | :--- |
| **Web Root (`index.php`)** | Di root folder (rawan terekspos) | Terpisah di dalam folder **`public/`** (seperti Laravel) |
| **Konfigurasi Environment** | Hardcode di `config.php`, `database.php`, `email.php` | Menggunakan file **`.env`** (`vlucas/phpdotenv`) + fungsi `env()` |
| **Base Controller** | Langsung `extends CI_Controller` | `MY_Controller`, `Public_Controller`, & `Auth_Controller` + `validate()`, `throttle()`, `success()`, `error()`, `handleException()` |
| **Sistem Layout View** | Panggil `header`, `content`, `footer` berulang-ulang | Cukup 1 baris: `$this->renderLayout('nama_view', $data)` |
| **Base Model (DDIA Ready)** | Tulis query manual di tiap model | `MY_Model` dengan CRUD, Safe `paginate()`, `transaction()`, `findForUpdate()`, `insertBatch()`, `$softDeletes`, `$fillable`, & `timestamps` |
| **Rate Limiter & Real IP** | Tidak tersedia | Multi-driver (`file`, `redis`, `memcached`, `apcu`) via `$this->throttle()` + verifikasi CIDR Cloudflare di `client_ip()` |
| **Database Migration CLI** | Harus buat controller manual | Siap pakai via CLI: `composer migrate` atau `php public/index.php migrate` |
| **File Storage & Uploads** | Campur di root folder | Terpisah antara **`storage/private/`** (`storage_path()`) dan **`public/uploads/`** (`upload_path()`, anti-RCE `.htaccess`) |
| **Autoloading Class** | Wajib `$this->load->library()` | Mendukung **Composer PSR-4 (`App\`)** untuk `application/Services/`, `application/Exceptions/`, dll. |
| **Observability & Helper** | `var_dump(); die;` & `log_message()` polos | Tersedia **`request_id()`**, **`log_context()`**, **`client_ip()`**, **`e()`**, **`csrf_field()`**, **`asset()`**, **`dd()`**, dan **`dump()`** |
| **Unit Testing** | Tidak tersedia / sulit dikonfigurasi | Siap pakai dengan **PHPUnit** (`composer test`) di folder `tests/Unit/` |

---

## 2. Spesifikasi & Tech Stack
- **Framework Core**: [pocketarc/codeigniter](https://github.com/pocketarc/codeigniter) (`v3.4.5`)
- **Kebutuhan PHP**: `>=8.5.12` dan `<8.6.0` (Composer: `~8.5.12`).
- **Dependency Manager**: Composer
- **Package Terpasang**:
  - `vlucas/phpdotenv` (`^5.6`) — Pembaca file `.env` standar industri.
  - `phpunit/phpunit` (`require-dev`) — Framework Unit Testing (hanya untuk development, tidak ikut ke production).

---

## 3. Struktur Direktori

```text
project-root/
├── application/
│   ├── cache/
│   │   └── sessions/          # Lokasi penyimpanan file session & rate limit (terisolasi & aman)
│   ├── config/                # Konfigurasi CI3 (database, email, redis, client_ip, migration otomatis dari .env)
│   ├── controllers/           # Controller aplikasi (Welcome.php, Migrate.php untuk CLI)
│   ├── core/
│   │   ├── MY_Controller.php  # Base Controller (Layout, JSON API, Throttle, Validation, Exception Handler)
│   │   └── MY_Model.php       # Base Model (CRUD, Transaction, Locking, Soft Delete, Safe Pagination)
│   ├── Exceptions/            # Custom Domain Exceptions PSR-4 (400, 401, 403, 404, 422, 429)
│   ├── helpers/
│   │   ├── app_helper.php       # Helper global: env(), e(), asset(), storage_path(), upload_path(), request_id(), log_context(), dd()
│   │   └── client_ip_helper.php # Helper pencari IP asli pengunjung & verifikasi CIDR Cloudflare: client_ip()
│   ├── migrations/            # File migrasi skema database (YYYYMMDDHHIISS_nama_migrasi.php)
│   ├── models/                # Model database (contoh: User_model.php)
│   ├── Services/              # Class Business Logic PSR-4 (AppInfoService, RateLimiterService)
│   └── views/
│       ├── layouts/           # Master template layout (contoh: main.php)
│       └── welcome_message.php
├── public/                    # DOCUMENT ROOT (Apache/Nginx/Laragon mengarah ke sini)
│   ├── assets/                # File statis CSS, JS, Gambar bawaan tema -> diakses via asset()
│   ├── uploads/               # File upload publik (foto produk, avatar) -> diakses via upload_path() & upload_url()
│   ├── .htaccess              # Rewrite URL (hapus index.php) & Security Headers
│   └── index.php              # Entry point utama yang bersih & ringkas
├── storage/
│   └── private/               # File upload rahasia/privat (KTP, invoice) di luar public/ -> diakses via storage_path()
├── system/                    # Core CodeIgniter 3.4.5 (Jangan diubah)
├── tests/                     # Unit Test PHPUnit
│   ├── bootstrap.php          # Bootstrapper CI3 + .env untuk testing
│   └── Unit/                  # Lokasi file unit test (*Test.php)
├── vendor/                    # Dependency Composer
├── .env                       # File konfigurasi lokal (TIDAK di-commit ke Git)
├── .env.example               # Template konfigurasi environment
├── CODING_STANDARD.md         # Panduan Standar Programming & Arsitektur (DDIA)
├── composer.json              # Konfigurasi dependency, PSR-4 ("App\\"), script "composer test" & "composer migrate"
└── phpunit.xml                # Konfigurasi PHPUnit
```

---

## 4. Cara Setup untuk Project Baru

### Langkah 1: Duplikasi / Clone Project
```powershell
git clone <url-repo-template-ini> nama-project-baru
cd nama-project-baru
```

### Langkah 2: Install Dependency Composer
```powershell
composer install
```

### Langkah 3: Buat & Atur File `.env`
```powershell
Copy-Item .env.example .env
```
Sesuaikan isi `.env`:
```dotenv
APP_NAME="Nama Aplikasi Anda"
APP_ENV=development
APP_KEY=isi_dengan_32_karakter_acak_rahasia
APP_URL=http://nama-project-baru.test/
APP_TIMEZONE=Asia/Jakarta
LOG_THRESHOLD=1

CSRF_PROTECTION=true
COOKIE_SECURE=false
TRUST_CLOUDFLARE=false

CACHE_DRIVER=file
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

DB_CONNECTION=mysqli
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="Nama Aplikasi Anda"
```

### Langkah 4: Aktifkan Database Autoload (Jika Project Memakai Database)
Contoh CRUD kategori memuat database sendiri, sehingga tidak membutuhkan perubahan autoload. Untuk memuat database pada seluruh controller:
Buka `application/config/autoload.php` baris 61 dan tambahkan `'database'`:
```php
$autoload['libraries'] = array('database', 'session');
```

### Langkah 5: Jalankan di Laragon / Web Server
- **Pengguna Laragon**: Klik **Reload** pada Laragon. Laragon otomatis mengenali folder `public/` dan membuat domain `http://nama-project-baru.test/`.
- **Pengguna `localhost/nama-project-baru`**: Tetap bisa diakses karena file `.htaccess` di root otomatis mengarahkan request ke `public/`.

### Pemeriksaan setup: `composer check`

Jalankan `composer check` setelah mengisi `.env`. Perintah ini memeriksa konfigurasi wajib, ekstensi PHP, izin direktori, driver cache, dan default keamanan production. Hasil `FAIL` menghasilkan exit code `1`; `WARN` menunjukkan hal yang perlu diperhatikan tetapi tidak menggagalkan pemeriksaan. Nilai rahasia tidak dicetak dan tidak ada file atau database yang diubah.

Pemeriksaan memakai PHP CLI dan pengguna terminal. Koneksi database/cache, izin pengguna web server, DocumentRoot, dan penerapan HTTPS tetap perlu diuji pada deployment sebenarnya. `composer check` tidak menggantikan `composer test`.

### Contoh CRUD kategori

1. Buat database kosong untuk proyek ini, lalu sesuaikan `DB_*` di `.env`.
2. Jalankan `composer check`, kemudian `composer migrate`. Migrasi contoh membuat tabel `categories` dengan nama, deskripsi, dan timestamps.
3. Buka `/categories` atau tautan dari halaman awal untuk mencoba tambah, edit, hapus, validasi, dan pagination (10 item per halaman).

Alurnya langsung `Categories` → `Category_model` → view, tanpa service tambahan untuk CRUD satu tabel. Nama wajib diisi (maksimal 80 karakter), deskripsi opsional (maksimal 500 karakter). Form memakai CSRF, output di-escape, field yang boleh ditulis dibatasi, dan operasi tulis memakai POST lalu redirect 303. GET tidak dapat menghapus data.

Contoh tersedia hanya pada `APP_ENV=development/testing` karena starter belum menyediakan login. Saat menyalinnya menjadi fitur aplikasi, gunakan autentikasi dan otorisasi proyek sebelum membuka akses production. Untuk menghapus contoh sebelum migrasi pertama, hapus controller `Categories.php`, model `Category_model.php`, folder view `categories/`, migrasi `20261017000000_create_categories.php`, tautan halaman awal, serta `CategoryHttpTest.php` dan router HTTP pengujiannya. Jika migrasi sudah pernah dijalankan pada database bersama, pertahankan riwayatnya dan buat migrasi baru untuk perubahan skema.

`composer test` mencakup alur CRUD melalui server HTTP lokal dan database SQLite sementara, termasuk CSRF, error validasi, output HTML, metode request, pagination, serta rollback migrasi. Tes ini membutuhkan ekstensi `sqlite3` dan `proc_open`; konfigurasi database tes dipisahkan dari database aplikasi.

---

## 5. Panduan Cara Pakai (Cheat Sheet)

### A. Konfigurasi `.env` & Fungsi `env()`
```php
$apiKey  = env('MIDTRANS_SERVER_KEY', 'default_key');
$isDebug = env('APP_ENV') === 'development';
```

---

### B. Membuat Controller & Master Layout (`MY_Controller`)
Tersedia 2 pilihan class induk yang bisa Anda `extends`:
1. **`Public_Controller`**: Untuk halaman publik tanpa login.
2. **`Auth_Controller`**: Untuk halaman yang **wajib login** (otomatis mengecek `$this->session->userdata('user_id')`).

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Produk extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Produk_model', 'produkModel');
    }

    public function index(): void
    {
        $this->renderLayout('produk/index', [
            'title'   => 'Daftar Produk',
            'produks' => $this->produkModel->all(),
        ]);
    }
}
```

---

### C. Standar REST API, Rate Limit, Validasi & Exception Handling
`MY_Controller` menyediakan method standar `$this->throttle()`, `$this->validate()`, `$this->success()`, `$this->error()`, dan `$this->handleException($e)`.

`validate()` mengembalikan hanya field yang memiliki rule, menggunakan hasil pemrosesan validator (termasuk `trim`). Helper ini mendukung nama field tingkat atas seperti `email`; nama bertingkat seperti `user[email]` atau `items[]` ditolak dengan `InvalidArgumentException`. Untuk struktur bertingkat gunakan library `form_validation` secara langsung dan pilih hasil validasi yang akan diteruskan ke model.

Tersedia juga class Exception siap pakai di `App\Exceptions\`:
- `ValidationException` (`422 Unprocessable Entity`)
- `UnauthorizedException` (`401 Unauthorized`)
- `ForbiddenException` (`403 Forbidden`)
- `NotFoundException` (`404 Not Found`)
- `TooManyRequestsException` (`429 Too Many Requests`)
- `RateLimiterUnavailableException` (`503 Service Unavailable`)
- `AppException` (`400 Bad Request` atau kode HTTP kustom)

Rate limiter memakai window yang dimulai pada percobaan pertama. Request yang ditolak tetap dihitung, tetapi tidak memperpanjang window. `clear($key)` mereset window tersebut.

- `file`: memakai `flock` untuk menghitung request paralel dan mereset counter. Cocok untuk satu server dengan filesystem lokal; semua worker harus memakai direktori yang sama.
- `redis` / `memcached`: memakai operasi native add-if-absent dan increment atomik, terpisah dari `CI_Cache`. Membutuhkan ekstensi PHP `redis` / `memcached` dan layanan yang tersedia. Redis memakai konfigurasi `REDIS_*` pada `.env`; Memcached memakai `application/config/memcached.php`. Semua instance aplikasi harus memakai backend yang sama.
- `apcu`: memakai add-if-absent dan compare-and-swap atomik. Membutuhkan ekstensi APCu aktif; counter hanya dibagi oleh worker yang menggunakan segmen APCu yang sama, bukan antarserver.

Jika penyimpanan gagal dibuka, dibaca, dikunci, atau ditulis, limiter melempar `RateLimiterUnavailableException`. Panggil `throttle()` di dalam blok `try/catch` seperti contoh berikut agar `handleException()` mengirim JSON **503**. Tidak ada fallback otomatis ke file karena perpindahan penyimpanan akan kehilangan counter lama. Batas yang terlampaui tetap menghasilkan **429** dan `Retry-After`.

Backend cache dapat kehilangan counter akibat eviction, flush, atau restart; sediakan kapasitas yang cukup dan hindari menghapus cache limiter saat window aktif. File counter tidak dihapus saat `clear()` agar lock request lain tetap mengacu ke file yang sama. Bersihkan file lama hanya saat worker dihentikan, bukan dengan menghapus file yang mungkin masih dikunci.

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_produk extends Public_Controller
{
    // POST /api/produk
    public function store(): void
    {
        try {
            // Rate Limiting: Maksimal 10 request per 60 detik per IP (mengikuti CACHE_DRIVER di .env: file/redis)
            $this->throttle('api_produk_store', 10, 60);

            // Form-encoded POST dengan token CSRF; gagal validasi menghasilkan 422
            $validated = $this->validate([
                'nama'  => ['label' => 'Nama Produk', 'rules' => 'required|min_length[3]'],
                'harga' => ['label' => 'Harga',       'rules' => 'required|numeric|greater_than[0]'],
            ]);

            $this->success($validated, 'Produk berhasil disimpan', 201);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }
}
```

---

### D. Model CRUD, Transaction, Locking & Soft Delete (`MY_Model`)
`transaction()` melempar exception jika transaksi gagal dimulai, query gagal, atau commit gagal. Kegagalan setelah transaksi dimulai memicu satu percobaan rollback; kegagalan rollback juga dilaporkan, dengan exception awal tetap tersedia sebagai penyebab. Mode debug database dimatikan sementara di dalam helper agar error query dapat ditangani, kemudian dipulihkan. Gunakan tabel/engine yang mendukung transaksi; kegagalan koneksi saat commit dapat membuat hasil commit tidak pasti, jadi jangan otomatis mengulang operasi pembayaran tanpa idempotensi.

Transaksi bersarang ditolak dengan `LogicException`. Satu service terluar memiliki transaksi; model/service yang dipanggil di dalamnya cukup menjalankan query pada koneksi yang sama tanpa memanggil `transaction()` lagi.

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class User_model extends MY_Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDeletes = false; // Ubah ke true jika tabel memiliki kolom deleted_at
    protected int $maxPerPage = 100;     // Batas aman pagination

    protected array $fillable = [
        'name', 'email', 'password', 'role', 'is_active',
    ];
}
```

**Contoh Pemakaian di Controller / Service:**
```php
// 1. Ambil data dengan pemilihan kolom spesifik
$userAktif = $this->userModel->all(['is_active' => 1], 'id DESC', ['id', 'name', 'email']);

// 2. Safe Pagination (otomatis dibatasi maksimal $maxPerPage)
$result = $this->userModel->paginate(15, 1, ['is_active' => 1], 'id DESC');

// 3. Atomic Database Transaction & Pessimistic Locking (SELECT ... FOR UPDATE)
$this->userModel->transaction(function () use ($userId) {
    $user = $this->userModel->findForUpdate($userId);
    $this->userModel->update($userId, ['is_active' => 0]);
});

// 4. Batch Insert
$this->userModel->insertBatch([
    ['name' => 'User A', 'email' => 'a@example.com'],
    ['name' => 'User B', 'email' => 'b@example.com'],
]);
```

---

### E. Database Migration via CLI (`composer migrate`)
Simpan file migrasi bertipe *timestamp* (`YYYYMMDDHHIISS_nama_migrasi.php`) di dalam `application/migrations/`, lalu jalankan dari terminal:
```powershell
# Menjalankan semua migrasi terbaru
composer migrate

# Atau rollback / pindah ke versi tertentu (0 untuk reset)
php public/index.php migrate version 0
```
*(Controller `Migrate.php` diproteksi dengan `is_cli()` sehingga tidak bisa diakses melalui browser).*

Argumen `version` wajib diisi satu bilangan bulat nonnegatif tanpa tanda atau nol di depan. Argumen kosong/tidak valid menghasilkan petunjuk penggunaan dan exit code `1` sebelum runner memuat database. `version 0` secara eksplisit menjalankan rollback seluruh migrasi; tidak ada default rollback.

---

### F. Manajemen File Upload (`storage/private` vs `public/uploads`)
Starter ini memisahkan lokasi penyimpanan file sesuai tingkat kerahasiaannya:
1. **File Publik (`public/uploads/`)**: Untuk gambar produk, avatar, atau banner yang boleh diakses via URL. Dilindungi `.htaccess` anti-RCE (memblokir eksekusi `.php`, `.html`, `.svg`, `.js`).
   - Path server: `upload_path('avatars/user-1.jpg')`
   - URL publik: `upload_url('avatars/user-1.jpg')`
2. **File Privat/Sensitif (`storage/private/`)**: Berada **di luar folder `public/`** untuk dokumen rahasia (foto KTP, bukti transfer, PDF invoice). Tidak memiliki URL publik langsung dan hanya bisa diunduh melalui Controller yang mengecek hak akses.
   - Path server: `storage_path('ktp/user-1.pdf')`

---

### G. Membuat Class PSR-4 Modern (`App\Services\...`)
Buat class di dalam `application/Services/` dan panggil langsung dengan `use App\Services\...` tanpa `$this->load->library()`.

---

### H. Helper Global (`e()`, `csrf_field()`, `asset()`, `client_ip()`, `request_id()`, `log_context()`, `dd()`)
1. **`e($string)`**: Anti-XSS HTML escaping di View (`<?= e($user->name) ?>`).
2. **`csrf_field()`**: Input hidden token CSRF untuk `<form method="POST">`.
3. **`asset($path)`**: URL ke file statis di `public/assets/`.
4. **`upload_path($path)` & `upload_url($path)`**: Path & URL ke file di `public/uploads/`.
5. **`storage_path($path)`**: Path filesystem ke folder privat `storage/private/`.
6. **`client_ip()`**: Mengambil IP asli pengunjung (mendukung normalisasi IPv6 & verifikasi CIDR Cloudflare jika `TRUST_CLOUDFLARE=true`).
7. **`request_id()`**: Correlation ID unik per request (otomatis dikirim juga di header `X-Request-Id`).
8. **`log_context($level, $message, $context)`**: Mencatat log terstruktur dalam format JSON beserta `request_id`.
9. **`dump($var)` & `dd($var)`**: Debugging variabel bergaya modern.

---

### I. Menjalankan Unit Test (PHPUnit)
Untuk menjalankan seluruh pengujian di folder `tests/Unit/`:
```powershell
composer test
```

---

## 6. Standar Keamanan (Security Hardening)

1. **CSRF Protection**:
   - `CSRF_PROTECTION=true` aktif secara default di `.env`.
   - `csrf_exclude_uris` kosong secara default; prefix `/api/` tidak melewati pemeriksaan CSRF. Endpoint berbasis cookie/session tetap membutuhkan perlindungan CSRF.
   - Pemeriksaan bawaan CI3 memvalidasi token pada POST form-encoded/multipart terhadap cookie. Kirim token menggunakan `csrf_field()` atau field POST bernama sesuai konfigurasi. JSON mentah tidak otomatis dibaca sebagai token CSRF, dan PUT/PATCH/DELETE tidak dilindungi oleh pemeriksaan POST bawaan ini.
   - Untuk API JSON berbasis token (tanpa autentikasi cookie) atau webhook, tambahkan pengecualian URI yang spesifik hanya setelah autentikasi token/verifikasi signature diterapkan. Untuk JSON atau metode lain yang tetap memakai session, implementasikan pemeriksaan CSRF yang sesuai terlebih dahulu; jangan mengecualikan seluruh `api/.*`.
2. **Cloudflare Real IP Anti-Spoofing (`TRUST_CLOUDFLARE`)**:
   - Jika domain menggunakan proxy Cloudflare, ubah `TRUST_CLOUDFLARE=true` di `.env`. Helper `client_ip()` hanya akan membaca `HTTP_CF_CONNECTING_IP` jika `REMOTE_ADDR` terbukti berasal dari daftar CIDR resmi Cloudflare di `application/config/client_ip.php`.
3. **Production Deployment Checklist**:
   - Gunakan `composer install --no-dev --optimize-autoloader` di server produksi.
   - Ubah `APP_ENV=production` dan `COOKIE_SECURE=true` (jika HTTPS) di `.env`.
   - Isi `APP_KEY` di `.env` dengan string acak sepanjang 32 karakter.
   - Arahkan Document Root web server langsung ke folder **`public/`**.
