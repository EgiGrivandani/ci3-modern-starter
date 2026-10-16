# CodeIgniter 3 Modern Starter Template (PHP 8.x – 8.5 Ready)

Starter template **CodeIgniter 3 (v3.4.5 - PocketARC Edition)** yang telah dimodifikasi dengan struktur, fitur, dan keamanan modern bergaya **Laravel**. Template ini dirancang dengan prinsip **Simple, Clean, Secure, Mudah Dibaca, dan Mudah Di-maintenance**, serta kompatibel penuh dengan **PHP 8.0 hingga PHP 8.5+**.

---

## Daftar Isi
1. [Apa Saja yang Dimodifikasi dari CI3 Standar?](#1-apa-saja-yang-dimodifikasi-dari-ci3-standar)
2. [Spesifikasi & Tech Stack](#2-spesifikasi--tech-stack)
3. [Struktur Direktori](#3-struktur-direktori)
4. [Cara Setup untuk Project Baru](#4-cara-setup-untuk-project-baru)
5. [Panduan Cara Pakai (Cheat Sheet)](#5-panduan-cara-pakai-cheat-sheet)
   - [A. Konfigurasi `.env` & Fungsi `env()`](#a-konfigurasi-env--fungsi-env)
   - [B. Membuat Controller & Master Layout (`MY_Controller`)](#b-membuat-controller--master-layout-my_controller)
   - [C. Membuat Endpoint REST API / JSON](#c-membuat-endpoint-rest-api--json)
   - [D. Membuat Model, CRUD & Pagination (`MY_Model`)](#d-membuat-model-crud--pagination-my_model)
   - [E. Membuat Class PSR-4 Modern (`App\Services\...`)](#e-membuat-class-psr-4-modern-appservices)
   - [F. Helper Global (`e()`, `csrf_field()`, `asset()`, `dd()`)](#f-helper-global-e-csrf_field-asset-dd)
6. [Standar Keamanan (Security Hardening)](#6-standar-keamanan-security-hardening)

---

## 1. Apa Saja yang Dimodifikasi dari CI3 Standar?

| Fitur | CI3 Standar (v3.1.13) | Template CI3 Modern Ini |
| :--- | :--- | :--- |
| **Versi PHP** | Maksimal PHP 7.4 / 8.0 | **PHP 8.0 s/d PHP 8.5+** (PocketARC v3.4.5) |
| **Web Root (`index.php`)** | Di root folder (rawan terekspos) | Terpisah di dalam folder **`public/`** (seperti Laravel) |
| **Konfigurasi Environment** | Hardcode di `config.php` & `database.php` | Menggunakan file **`.env`** (`vlucas/phpdotenv`) + fungsi `env()` |
| **Base Controller** | Langsung `extends CI_Controller` | Memiliki `MY_Controller`, `Public_Controller`, & `Auth_Controller` |
| **Sistem Layout View** | Panggil `header`, `content`, `footer` berulang-ulang | Cukup 1 baris: `$this->renderLayout('nama_view', $data)` |
| **Base Model (CRUD)** | Tulis query manual di tiap model | `MY_Model` dengan `all()`, `find()`, `paginate()`, `create()`, `update()`, `delete()`, `$fillable`, & `timestamps` |
| **Autoloading Class** | Wajib `$this->load->library()` | Mendukung **Composer PSR-4 (`App\`)** untuk folder `application/` |
| **Helper Global** | `var_dump(); die;` & `html_escape()` | Tersedia **`e()`**, **`csrf_field()`**, **`asset()`**, **`dd()`**, dan **`dump()`** di `application/helpers/app_helper.php` |

---

## 2. Spesifikasi & Tech Stack
- **Framework Core**: [pocketarc/codeigniter](https://github.com/pocketarc/codeigniter) (`v3.4.5`)
- **Kebutuhan PHP**: Minimal PHP `8.0` (Direkomendasikan **PHP 8.2 – 8.5**)
- **Dependency Manager**: Composer
- **Package Terpasang**:
  - `vlucas/phpdotenv` (`^5.6`) — Pembaca file `.env` standar industri (sama dengan yang dipakai Laravel).

---

## 3. Struktur Direktori

```text
project-root/
├── application/
│   ├── cache/
│   │   └── sessions/        # Lokasi penyimpanan file session (terisolasi & aman)
│   ├── config/              # Konfigurasi CI3 (otomatis membaca nilai dari .env)
│   ├── controllers/         # Controller aplikasi (contoh: Welcome.php)
│   ├── core/
│   │   ├── MY_Controller.php # Base Controller (MY_Controller, Public_Controller, Auth_Controller)
│   │   └── MY_Model.php      # Base Model (Eloquent-like CRUD, paginate, fillable, timestamps)
│   ├── helpers/
│   │   └── app_helper.php    # Fungsi global: env(), e(), asset(), csrf_field(), dump(), dd()
│   ├── models/              # Model database (contoh: User_model.php)
│   ├── Services/            # Tempat membuat class OOP modern PSR-4 (namespace App\Services)
│   └── views/
│       ├── layouts/         # Master template layout (contoh: main.php)
│       └── welcome_message.php
├── public/                  # DOCUMENT ROOT (Apache/Nginx/Laragon mengarah ke sini)
│   ├── assets/              # Taruh file CSS, JS, Gambar di dalam public/assets/
│   ├── .htaccess            # Rewrite URL (hapus index.php) & Security Headers
│   └── index.php            # Entry point utama yang bersih & ringkas
├── system/                  # Core CodeIgniter 3.4.5 (Jangan diubah)
├── vendor/                  # Dependency Composer
├── .env                     # File konfigurasi lokal (TIDAK di-commit ke Git)
├── .env.example             # Template konfigurasi environment
├── .gitignore               # Mengabaikan .env, vendor/, log, dan file session
├── .htaccess                # Proteksi folder internal & redirect otomatis ke public/
└── composer.json            # Konfigurasi dependency, PSR-4 ("App\\"), & autoload app_helper.php
```

---

## 4. Cara Setup untuk Project Baru

Setiap kali Anda ingin memulai project baru menggunakan template ini, ikuti langkah berikut:

### Langkah 1: Duplikasi / Clone Project
```powershell
git clone <url-repo-template-ini> nama-project-baru
cd nama-project-baru
```
*(Jika ingin mereset riwayat git agar menjadi repo baru dari nol, hapus folder `.git` lalu jalankan `git init`).*

### Langkah 2: Install Dependency Composer
```powershell
composer install
```

### Langkah 3: Buat & Atur File `.env`
Salin `.env.example` menjadi `.env`:
```powershell
Copy-Item .env.example .env
```
Buka file `.env` dan sesuaikan isinya:
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
Secara default, koneksi database belum di-autoload agar template tetap bisa dibuka meskipun database belum dibuat.
Jika database Anda sudah siap di MySQL/MariaDB, buka `application/config/autoload.php` baris 61 dan tambahkan `'database'`:
```php
$autoload['libraries'] = array('database', 'session');
```

### Langkah 5: Jalankan di Laragon / Web Server
- **Pengguna Laragon**: Cukup klik **Reload** pada Laragon. Laragon otomatis mengenali folder `public/` dan membuat domain `http://nama-project-baru.test/`.
- **Pengguna `localhost/nama-project-baru`**: Tetap bisa diakses karena file `.htaccess` di root otomatis mengarahkan request ke `public/`.

---

## 5. Panduan Cara Pakai (Cheat Sheet)

### A. Konfigurasi `.env` & Fungsi `env()`
Anda bisa memanggil fungsi `env('NAMA_KEY', 'nilai_default')` di mana saja (Config, Controller, Model, View, maupun Service).
```php
$apiKey  = env('MIDTRANS_SERVER_KEY', 'default_key');
$isDebug = env('APP_ENV') === 'development';
```
> Nilai `'true'`, `'false'`, `'null'`, dan `'empty'` di dalam `.env` otomatis dikonversi menjadi tipe data boolean/`null`/string kosong oleh fungsi `env()`.

---

### B. Membuat Controller & Master Layout (`MY_Controller`)
Lokasi file: `application/controllers/`
Tersedia 2 pilihan class induk yang bisa Anda `extends`:
1. **`Public_Controller`**: Untuk halaman yang bebas diakses tanpa login (Landing page, halaman Login, dll.).
2. **`Auth_Controller`**: Untuk halaman yang **wajib login** (Dashboard, Admin, Profil). Secara otomatis mengecek `$this->session->userdata('user_id')`. Jika belum login, otomatis me-redirect ke `/login` (atau mengirim JSON `401 Unauthenticated` jika diakses via AJAX).

**Contoh Controller (`application/controllers/Produk.php`):**
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
        $data = [
            'title'   => 'Daftar Produk',
            'produks' => $this->produkModel->all(),
        ];

        // Merender application/views/produk/index.php di dalam application/views/layouts/main.php
        $this->renderLayout('produk/index', $data);

        // Atau jika ingin menggunakan file layout berbeda (misal application/views/layouts/admin.php):
        // $this->renderLayout('produk/index', $data, 'layouts/admin');

        // Atau jika ingin merender view biasa TANPA layout:
        // $this->render('produk/cetak_pdf', $data);
    }
}
```

---

### C. Membuat Endpoint REST API / JSON
`MY_Controller` sudah menyediakan method `$this->json($payload, $statusCode)` untuk mengirim output JSON dan `$this->inputJson()` untuk membaca body JSON (`application/json`) dari `POST`/`PUT`.

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_produk extends Public_Controller
{
    // GET /api/produk
    public function index(): void
    {
        $this->json([
            'status' => true,
            'data'   => ['id' => 1, 'nama' => 'Kopi Arabika'],
        ], 200);
    }

    // POST /api/produk
    public function store(): void
    {
        // Mengambil JSON body dari request (misal dari Fetch / Axios / Postman)
        $body = $this->inputJson();

        if (empty($body['nama'])) {
            $this->json(['status' => false, 'message' => 'Nama produk wajib diisi'], 422);
            return;
        }

        $this->json(['status' => true, 'message' => 'Berhasil disimpan'], 201);
    }
}
```

---

### D. Membuat Model, CRUD & Pagination (`MY_Model`)
Lokasi file: `application/models/`
Cukup `extends MY_Model` dan tentukan `$table`, `$primaryKey`, `$timestamps`, serta `$fillable`.

**Contoh Model (`application/models/User_model.php`):**
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class User_model extends MY_Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    // Jika true, otomatis mengisi kolom created_at saat create() dan updated_at saat update()
    protected bool $timestamps = true;

    // Kolom yang diizinkan untuk disimpan (mencegah Mass Assignment Vulnerability)
    protected array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];
}
```

**Daftar Method Bawaan `MY_Model` yang Langsung Bisa Dipakai di Controller:**
```php
$this->load->model('User_model', 'userModel');

// 1. Ambil semua data (bisa ditambah filter array & order by)
$semuaUser = $this->userModel->all();
$userAktif = $this->userModel->all(['is_active' => 1], 'id DESC');

// 2. Pagination simpel (mengembalikan array: data, total, per_page, current_page, last_page)
$page   = (int) ($this->input->get('page') ?: 1);
$result = $this->userModel->paginate(10, $page, ['is_active' => 1], 'id DESC');

// 3. Cari 1 baris berdasarkan Primary Key (id) atau kolom tertentu
$user        = $this->userModel->find(10);
$userByEmail = $this->userModel->findBy('email', 'budi@example.com');

// 4. Insert data baru (otomatis difilter sesuai $fillable & isi created_at/updated_at)
$insertedId = $this->userModel->create([
    'name'      => 'Budi Santoso',
    'email'     => 'budi@example.com',
    'password'  => password_hash('rahasia123', PASSWORD_BCRYPT),
    'is_active' => 1,
]);

// 5. Update data berdasarkan ID (otomatis update kolom updated_at)
$this->userModel->update(10, [
    'name' => 'Budi Santoso Baru',
]);

// 6. Hapus data & Hitung jumlah data
$this->userModel->delete(10);
$totalAktif = $this->userModel->count(['is_active' => 1]);
```

---

### E. Membuat Class PSR-4 Modern (`App\Services\...`)
Berkat konfigurasi PSR-4 `"App\\": "application/"` di `composer.json`, Anda bisa membuat folder berhuruf kapital di dalam `application/` (seperti `application/Services/`, `application/Repositories/`) dan langsung memanggilnya dengan `use App\...` seperti di Laravel tanpa `$this->load->library()`.

**Contoh (`application/Services/PembayaranService.php`):**
```php
<?php

namespace App\Services;

defined('BASEPATH') OR exit('No direct script access allowed');

class PembayaranService
{
    public function hitungTotal(int $harga, int $qty): int
    {
        return $harga * $qty;
    }
}
```
**Cara panggil di Controller:**
```php
use App\Services\PembayaranService;

$service = new PembayaranService();
$total   = $service->hitungTotal(50000, 3);
```

---

### F. Helper Global (`e()`, `csrf_field()`, `asset()`, `dd()`)
Semua helper global berada di satu file rapi: `application/helpers/app_helper.php`.

1. **`e($string)` — Anti-XSS di View**
   Gunakan setiap kali mencetak variabel ke dalam HTML:
   ```php
   <h1>Halo, <?= e($user->name) ?></h1>
   ```
2. **`csrf_field()` — Input Hidden CSRF untuk Form HTML (Seperti `@csrf` di Laravel)**
   ```php
   <form action="<?= base_url('produk/simpan') ?>" method="POST">
       <?= csrf_field() ?>
       <input type="text" name="nama">
       <button type="submit">Simpan</button>
   </form>
   ```
3. **`asset($path)` — URL ke File Statis di `public/assets/`**
   ```php
   <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
   <script src="<?= asset('js/app.js') ?>"></script>
   ```
4. **`dd($var)` & `dump($var)` — Debugging Cepat**
   - `dump($var1, $var2)`: Menampilkan isi variabel dengan kotak dark-mode rapi tanpa menghentikan script.
   - `dd($var1, $var2)`: Menampilkan isi variabel lalu langsung menghentikan eksekusi script (*Dump and Die*).

---

## 6. Standar Keamanan (Security Hardening)

1. **CSRF Protection & Pengecualian REST API (`api/*`)**:
   - `CSRF_PROTECTION=true` aktif secara default di `.env`. Gunakan `<?= csrf_field() ?>` atau `<?= form_open('...') ?>` di setiap form HTML `POST`.
   - Semua route yang diawali dengan `api/` (misal `api/health`, `api/login`) sudah di-whitelist di `$config['csrf_exclude_uris'] = array('api/.*');` pada `application/config/config.php`.
2. **Production Deployment Checklist**:
   Saat meng-upload project ke server produksi (Hosting / VPS):
   - Ubah `APP_ENV=production` di `.env` (otomatis mematikan tampilan error PHP ke layar pengunjung).
   - Ubah `COOKIE_SECURE=true` di `.env` jika domain sudah menggunakan `https://`.
   - Ganti `APP_KEY` di `.env` dengan string acak unik sepanjang 32 karakter.
   - Pastikan Document Root domain di cPanel / Nginx / Apache diarahkan langsung ke folder **`public/`**.
