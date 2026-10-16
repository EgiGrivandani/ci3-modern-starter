# CodeIgniter 3 Modern Starter Template (PHP 8.x – 8.5 Ready)

Starter template **CodeIgniter 3 (v3.4.5 - PocketARC Edition)** yang telah dimodifikasi dengan struktur, fitur, keamanan modern bergaya **Laravel**, serta fondasi **Engineering Standard berbasis DDIA (*Designing Data-Intensive Applications*) & Clean Architecture**. Template ini dirancang dengan prinsip **Reliable, Scalable, Maintainable, Clean, dan Secure**, serta kompatibel penuh dengan **PHP 8.0 hingga PHP 8.5+**.

> **📘 Dokumen Standar Programming**: Baca panduan lengkap aturan arsitektur 3-Layer, penamaan kode, transaksi database, dan standar API di **[CODING_STANDARD.md](CODING_STANDARD.md)**.

---

## Daftar Isi
1. [Apa Saja yang Dimodifikasi dari CI3 Standar?](#1-apa-saja-yang-dimodifikasi-dari-ci3-standar)
2. [Spesifikasi & Tech Stack](#2-spesifikasi--tech-stack)
3. [Struktur Direktori](#3-struktur-direktori)
4. [Cara Setup untuk Project Baru](#4-cara-setup-untuk-project-baru)
5. [Panduan Cara Pakai (Cheat Sheet)](#5-panduan-cara-pakai-cheat-sheet)
   - [A. Konfigurasi `.env` & Fungsi `env()`](#a-konfigurasi-env--fungsi-env)
   - [B. Membuat Controller & Master Layout (`MY_Controller`)](#b-membuat-controller--master-layout-my_controller)
   - [C. Standar REST API, Validasi Input & Exception Handling](#c-standar-rest-api-validasi-input--exception-handling)
   - [D. Model CRUD, Transaction, Locking & Soft Delete (`MY_Model`)](#d-model-crud-transaction-locking--soft-delete-my_model)
   - [E. Membuat Class PSR-4 Modern (`App\Services\...`)](#e-membuat-class-psr-4-modern-appservices)
   - [F. Helper Global (`e()`, `csrf_field()`, `asset()`, `request_id()`, `log_context()`, `dd()`)](#f-helper-global-e-csrf_field-asset-request_id-log_context-dd)
   - [G. Menjalankan Unit Test (PHPUnit)](#g-menjalankan-unit-test-phpunit)
6. [Standar Keamanan (Security Hardening)](#6-standar-keamanan-security-hardening)

---

## 1. Apa Saja yang Dimodifikasi dari CI3 Standar?

| Fitur | CI3 Standar (v3.1.13) | Template CI3 Modern Ini |
| :--- | :--- | :--- |
| **Versi PHP** | Maksimal PHP 7.4 / 8.0 | **PHP 8.0 s/d PHP 8.5+** (PocketARC v3.4.5) |
| **Web Root (`index.php`)** | Di root folder (rawan terekspos) | Terpisah di dalam folder **`public/`** (seperti Laravel) |
| **Konfigurasi Environment** | Hardcode di `config.php` & `database.php` | Menggunakan file **`.env`** (`vlucas/phpdotenv`) + fungsi `env()` |
| **Base Controller** | Langsung `extends CI_Controller` | `MY_Controller`, `Public_Controller`, & `Auth_Controller` + `validate()`, `success()`, `error()`, `handleException()` |
| **Sistem Layout View** | Panggil `header`, `content`, `footer` berulang-ulang | Cukup 1 baris: `$this->renderLayout('nama_view', $data)` |
| **Base Model (DDIA Ready)** | Tulis query manual di tiap model | `MY_Model` dengan CRUD, Safe `paginate()`, `transaction()`, `findForUpdate()`, `insertBatch()`, `$softDeletes`, `$fillable`, & `timestamps` |
| **Autoloading Class** | Wajib `$this->load->library()` | Mendukung **Composer PSR-4 (`App\`)** untuk `application/Services/`, `application/Exceptions/`, dll. |
| **Observability & Helper** | `var_dump(); die;` & `log_message()` polos | Tersedia **`request_id()`**, **`log_context()`**, **`e()`**, **`csrf_field()`**, **`asset()`**, **`dd()`**, dan **`dump()`** |
| **Unit Testing** | Tidak tersedia / sulit dikonfigurasi | Siap pakai dengan **PHPUnit** (`composer test`) di folder `tests/Unit/` |

---

## 2. Spesifikasi & Tech Stack
- **Framework Core**: [pocketarc/codeigniter](https://github.com/pocketarc/codeigniter) (`v3.4.5`)
- **Kebutuhan PHP**: Minimal PHP `8.0` (Direkomendasikan **PHP 8.2 – 8.5**)
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
│   │   └── sessions/          # Lokasi penyimpanan file session (terisolasi & aman)
│   ├── config/                # Konfigurasi CI3 (otomatis membaca nilai dari .env)
│   ├── controllers/           # Controller aplikasi (contoh: Welcome.php)
│   ├── core/
│   │   ├── MY_Controller.php  # Base Controller (Layout, Standard JSON API, Validation, Exception Handler)
│   │   └── MY_Model.php       # Base Model (CRUD, Transaction, Locking, Soft Delete, Safe Pagination)
│   ├── Exceptions/            # Custom Domain Exceptions PSR-4 (AppException, ValidationException, NotFoundException)
│   ├── helpers/
│   │   └── app_helper.php     # Fungsi global: env(), e(), asset(), csrf_field(), request_id(), log_context(), dump(), dd()
│   ├── models/                # Model database (contoh: User_model.php)
│   ├── Services/              # Tempat membuat class Business Logic PSR-4 (namespace App\Services)
│   └── views/
│       ├── layouts/           # Master template layout (contoh: main.php)
│       └── welcome_message.php
├── public/                    # DOCUMENT ROOT (Apache/Nginx/Laragon mengarah ke sini)
│   ├── assets/                # Taruh file CSS, JS, Gambar di dalam public/assets/
│   ├── .htaccess              # Rewrite URL (hapus index.php) & Security Headers
│   └── index.php              # Entry point utama yang bersih & ringkas
├── system/                    # Core CodeIgniter 3.4.5 (Jangan diubah)
├── tests/                     # Unit Test PHPUnit
│   ├── bootstrap.php          # Bootstrapper CI3 + .env untuk testing
│   └── Unit/                  # Lokasi file unit test (*Test.php)
├── vendor/                    # Dependency Composer
├── .env                       # File konfigurasi lokal (TIDAK di-commit ke Git)
├── .env.example               # Template konfigurasi environment
├── CODING_STANDARD.md         # Panduan Standar Programming & Arsitektur (DDIA)
├── composer.json              # Konfigurasi dependency, PSR-4 ("App\\"), & script "composer test"
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

CSRF_PROTECTION=true
COOKIE_SECURE=false

DB_CONNECTION=mysqli
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=root
DB_PASSWORD=
```

### Langkah 4: Aktifkan Database Autoload (Jika Project Memakai Database)
Buka `application/config/autoload.php` baris 61 dan tambahkan `'database'`:
```php
$autoload['libraries'] = array('database', 'session');
```

### Langkah 5: Jalankan di Laragon / Web Server
- **Pengguna Laragon**: Klik **Reload** pada Laragon. Laragon otomatis mengenali folder `public/` dan membuat domain `http://nama-project-baru.test/`.
- **Pengguna `localhost/nama-project-baru`**: Tetap bisa diakses karena file `.htaccess` di root otomatis mengarahkan request ke `public/`.

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

### C. Standar REST API, Validasi Input & Exception Handling
`MY_Controller` menyediakan method standar `$this->success()`, `$this->error()`, `$this->validate()`, dan `$this->handleException($e)`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Exceptions\NotFoundException;

class Api_produk extends Public_Controller
{
    // POST /api/produk
    public function store(): void
    {
        try {
            // Otomatis memvalidasi body JSON dan melempar ValidationException (422) jika gagal
            $validated = $this->validate([
                'nama'  => ['label' => 'Nama Produk', 'rules' => 'required|min_length[3]'],
                'harga' => ['label' => 'Harga',       'rules' => 'required|numeric|greater_than[0]'],
            ], $this->inputJson());

            $this->success($validated, 'Produk berhasil disimpan', 201);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }
}
```

---

### D. Model CRUD, Transaction, Locking & Soft Delete (`MY_Model`)
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

### E. Membuat Class PSR-4 Modern (`App\Services\...`)
Buat class di dalam `application/Services/` dan panggil langsung dengan `use App\Services\...` tanpa `$this->load->library()`.

---

### F. Helper Global (`e()`, `csrf_field()`, `asset()`, `request_id()`, `log_context()`, `dd()`)
1. **`e($string)`**: Anti-XSS HTML escaping di View (`<?= e($user->name) ?>`).
2. **`csrf_field()`**: Input hidden token CSRF untuk `<form method="POST">`.
3. **`asset($path)`**: URL ke file statis di `public/assets/`.
4. **`request_id()`**: Correlation ID unik per request (otomatis dikirim juga di header `X-Request-Id`).
5. **`log_context($level, $message, $context)`**: Mencatat log terstruktur dalam format JSON beserta `request_id`.
6. **`dump($var)` & `dd($var)`**: Debugging variabel bergaya modern.

---

### G. Menjalankan Unit Test (PHPUnit)
Untuk menjalankan seluruh pengujian di folder `tests/Unit/`:
```powershell
composer test
```

---

## 6. Standar Keamanan (Security Hardening)

1. **CSRF Protection & Pengecualian REST API (`api/*`)**:
   - `CSRF_PROTECTION=true` aktif secara default di `.env`.
   - Semua route yang diawali `api/` sudah di-whitelist di `$config['csrf_exclude_uris'] = array('api/.*');` pada `application/config/config.php`.
2. **Production Deployment Checklist**:
   - Gunakan `composer install --no-dev --optimize-autoloader` di server produksi.
   - Ubah `APP_ENV=production` dan `COOKIE_SECURE=true` (jika HTTPS) di `.env`.
   - Isi `APP_KEY` di `.env` dengan string acak sepanjang 32 karakter.
   - Arahkan Document Root web server langsung ke folder **`public/`**.
