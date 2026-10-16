# Standar Programming & Arsitektur (Engineering Standard)

Dokumen ini adalah panduan baku (**Engineering Standard**) untuk seluruh developer yang membangun aplikasi menggunakan **CodeIgniter 3 Modern Starter**. Standar ini dirancang dengan mengadopsi prinsip-prinsip dari buku **DDIA (*Designing Data-Intensive Applications* — Martin Kleppmann)** serta **Clean Architecture / Clean Code**.

---

## Daftar Isi
1. [Tiga Pilar Utama (Prinsip DDIA)](#1-tiga-pilar-utama-prinsip-ddia)
2. [Arsitektur 3-Layer (Separation of Concerns)](#2-arsitektur-3-layer-separation-of-concerns)
3. [Standar Penamaan (Naming Conventions)](#3-standar-penamaan-naming-conventions)
4. [Standar Keandalan Data & Transaksi (Reliability)](#4-standar-keandalan-data--transaksi-reliability)
5. [Standar Skalabilitas & Efisiensi Query (Scalability)](#5-standar-skalabilitas--efisiensi-query-scalability)
6. [Standar API Response, Error Handling & Observability (Maintainability)](#6-standar-api-response-error-handling--observability-maintainability)
7. [Standar Keamanan (Security Baseline)](#7-standar-keamanan-security-baseline)
8. [Standar Pengujian (Unit Testing)](#8-standar-pengujian-unit-testing)

---

## 1. Tiga Pilar Utama (Prinsip DDIA)

Setiap baris kode yang ditulis wajib memenuhi 3 pilar berikut:
1. **Reliability (Keandalan)**: Aplikasi harus tetap bekerja dengan benar meskipun terjadi kegagalan parsial, request ganda (*concurrent request*), atau input yang tidak valid. Data tidak boleh korup (*half-written data*).
2. **Scalability (Skalabilitas)**: Kode yang berjalan lancar saat data masih 100 baris harus tetap aman saat data tumbuh menjadi 1 juta baris (tidak ada query tanpa batas, tidak menarik kolom yang tidak perlu).
3. **Maintainability (Kemudahan Pemeliharaan)**:
   - **Operability**: Mudah dilacak saat terjadi masalah di production menggunakan `request_id()` dan `log_context()`.
   - **Simplicity**: Kode bersih, pendek, dan mengikuti pola yang seragam di seluruh modul.
   - **Evolvability**: Logika bisnis terpisah di `App\Services\` sehingga mudah diubah atau di-test tanpa merusak Controller/View.

---

## 2. Arsitektur 3-Layer (Separation of Concerns)

Aplikasi memisahkan tanggung jawab ke dalam 3 lapisan tegas:

```text
HTTP Request ──► Controller (HTTP & Validasi) ──► Service (Logika Bisnis) ──► Model (Query Database)
```

### A. Controller (`application/controllers/`) — *Thin Controller*
- **Tanggung Jawab**:
  - Menerima HTTP request (`GET`, `POST`, JSON body).
  - Menjalankan validasi input via `$this->validate(...)`.
  - Memanggil **Service** atau **Model** sederhana.
  - Mengembalikan View (`$this->renderLayout()`) atau JSON (`$this->success()` / `$this->handleException()`).
- **Dilarang**:
  - Menulis query SQL / `$this->db->...` langsung di dalam Controller.
  - Menulis perhitungan bisnis yang kompleks (hitung pajak, diskon bertingkat, mutasi saldo) di dalam Controller.

### B. Service Layer (`application/Services/`) — *Business Logic (PSR-4 `App\Services`)*
- **Tanggung Jawab**:
  - Menyimpan aturan bisnis (*domain logic*), kalkulasi, integrasi API pihak ketiga, dan koordinasi antar beberapa Model.
  - Melempar `AppException` / `ValidationException` / `NotFoundException` jika aturan bisnis dilanggar.
- **Dilarang**:
  - Mengakses `$_POST`, `$_GET`, atau mencetak HTML/JSON langsung (`echo`) di dalam Service.

### C. Model Layer (`application/models/`) — *Data Access*
- **Tanggung Jawab**:
  - Mewarisi (`extends`) `MY_Model`.
  - Mendefinisikan `$table`, `$primaryKey`, `$fillable`, `$timestamps`, dan `$softDeletes`.
  - Menyediakan fungsi query spesifik tabel tersebut (misal: `findActiveByEmail()`).

---

## 3. Standar Penamaan (Naming Conventions)

| Komponen | Lokasi | Aturan Penamaan | Contoh |
| :--- | :--- | :--- | :--- |
| **Controller** | `application/controllers/` | `Ucfirst_snake_case` (standar CI3) | `Order_history.php` (`class Order_history`) |
| **Model** | `application/models/` | `Ucfirst_snake_case` + akhiran `_model` | `User_model.php` (`class User_model`) |
| **Service (PSR-4)** | `application/Services/` | `PascalCase` + akhiran `Service` | `PaymentService.php` (`App\Services\PaymentService`) |
| **Exception (PSR-4)** | `application/Exceptions/` | `PascalCase` + akhiran `Exception` | `NotFoundException.php` (`App\Exceptions\NotFoundException`) |
| **Method & Variabel** | Seluruh Class | `camelCase` + **Type Hinting** | `public function calculateTotal(int $qty): int` |
| **Tabel Database** | Database | `snake_case` (jamak/plural) | `users`, `order_items`, `payment_logs` |
| **Kolom Database** | Database | `snake_case` | `id`, `user_id`, `is_active`, `created_at` |
| **View File** | `application/views/` | `snake_case` dalam sub-folder modul | `orders/detail_invoice.php` |

> **Wajib**: Selalu gunakan deklarasi tipe parameter dan *return type* (`: void`, `: array`, `: ?object`, `: int`) pada setiap method baru.

---

## 4. Standar Keandalan Data & Transaksi (Reliability)

### A. Gunakan Database Transaction untuk Multi-Query
Setiap operasi yang mengubah **lebih dari 1 tabel** (misal: simpan *header order* + simpan *detail item* + potong *stok*) **WAJIB** dibungkus dengan `$this->model->transaction()`:

```php
$orderId = $this->orderModel->transaction(function () use ($orderData, $items) {
    $id = $this->orderModel->create($orderData);

    foreach ($items as &$item) {
        $item['order_id'] = $id;
    }
    $this->orderItemModel->insertBatch($items);

    return $id;
});
```
Jika ada satu saja query gagal atau terjadi Exception di tengah jalan, seluruh perubahan otomatis di-*rollback*.

### B. Cegah Race Condition dengan Pessimistic Locking (`findForUpdate`)
Saat memproses data sensitif seperti **potong stok** atau **potong saldo dompet**, jangan gunakan `find()` biasa karena 2 request bersamaan bisa membaca nilai saldo yang sama (*lost update*). Gunakan `findForUpdate()` di dalam `transaction()`:

```php
$this->walletModel->transaction(function () use ($userId, $nominal) {
    // Mengunci baris (SELECT * FROM wallets WHERE id = ? FOR UPDATE)
    $wallet = $this->walletModel->findForUpdate($userId);

    if (!$wallet || $wallet->balance < $nominal) {
        throw new \App\Exceptions\AppException('Saldo tidak mencukupi.', 422, 'INSUFFICIENT_BALANCE');
    }

    $this->walletModel->update($userId, [
        'balance' => $wallet->balance - $nominal,
    ]);
});
```

### C. Gunakan Soft Deletes untuk Data Transaksional Penting
Pada tabel yang memiliki nilai histori/audit (seperti `users`, `orders`, `invoices`), aktifkan `$softDeletes = true;` di Model dan sediakan kolom `deleted_at DATETIME NULL` di tabel database:

```php
class Order_model extends MY_Model
{
    protected string $table = 'orders';
    protected bool $softDeletes = true;
}
```
Saat `$this->orderModel->delete($id)` dipanggil, data tidak terhapus permanen melainkan mengisi `deleted_at`, dan otomatis disembunyikan dari `all()`, `find()`, maupun `paginate()`.

---

## 5. Standar Skalabilitas & Efisiensi Query (Scalability)

1. **Hindari `all()` pada Tabel Transaksi Besar**:
   Gunakan `paginate()` untuk menampilkan daftar data ke user. `MY_Model::paginate()` secara otomatis membatasi jumlah maksimal baris per halaman (`$maxPerPage = 100`) agar server tidak kehabisan memori jika user mengirim `?per_page=999999`.
2. **Pilih Kolom Spesifik (`$columns`)**:
   Jangan mengambil seluruh kolom (`SELECT *`) jika hanya butuh beberapa kolom untuk dropdown atau tabel ringkas:
   ```php
   $users = $this->userModel->all(['is_active' => 1], 'name ASC', ['id', 'name', 'email']);
   ```
3. **Gunakan `insertBatch()` untuk Insert Banyak Baris**:
   Jangan melakukan `$model->create()` di dalam *loop* `foreach`. Kumpulkan array-nya lalu panggil `$model->insertBatch($rows)`.

---

## 6. Standar API Response, Error Handling & Observability (Maintainability)

### A. Format Baku JSON Response
Seluruh endpoint JSON / AJAX wajib menggunakan `$this->success()` dan `$this->error()` (atau `$this->handleException($e)`):

- **Response Sukses (`$this->success($data, $message, $statusCode, $meta)`)**:
  ```json
  {
    "status": true,
    "message": "Data produk berhasil diambil",
    "data": { "id": 1, "name": "Kopi" }
  }
  ```
- **Response Error (`$this->error(...)` / `$this->handleException($e)`)**:
  ```json
  {
    "status": false,
    "error_code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "request_id": "a1b2c3d4e5f60718",
    "errors": {
      "email": "The Email field must contain a valid email address."
    }
  }
  ```

### B. Pola Standar Controller dengan `validate()` dan `handleException()`
```php
public function store(): void
{
    try {
        $payload = $this->validate([
            'name'  => ['label' => 'Nama Produk', 'rules' => 'required|min_length[3]'],
            'price' => ['label' => 'Harga',       'rules' => 'required|numeric|greater_than[0]'],
        ], $this->inputJson());

        $id = $this->productModel->create($payload);
        $this->success(['id' => $id], 'Produk berhasil ditambahkan', 201);
    } catch (Throwable $e) {
        $this->handleException($e);
    }
}
```

### C. Structured Logging dengan `log_context()`
Jangan gunakan `error_log()` mentah. Gunakan `log_context($level, $message, $context)` agar setiap catatan log di `application/logs/` otomatis memiliki `request_id` yang sama dengan header `X-Request-Id` dan response JSON pengunjung:

```php
log_context('error', 'Gagal memproses pembayaran Midtrans', [
    'order_id' => $orderId,
    'http_code' => 504,
]);
```

---

## 7. Standar Keamanan (Security Baseline)

1. **Anti Mass-Assignment**: Setiap Model **wajib** mengisi properti `protected array $fillable = [...]`.
2. **Anti XSS di View**: Setiap pencetakan variabel di file `.php` view **wajib** dibungkus fungsi `<?= e($var) ?>`.
3. **Proteksi CSRF**: Setiap `<form method="POST">` HTML **wajib** menyertakan `<?= csrf_field() ?>`.
4. **Password Hashing**: Wajib menggunakan fungsi bawaan PHP `password_hash($plain, PASSWORD_BCRYPT)` dan `password_verify($plain, $hash)`. Dilarang keras menggunakan `md5()` atau `sha1()`.

---

## 8. Standar Pengujian (Unit Testing)

Starter ini sudah dilengkapi dengan konfigurasi **PHPUnit** di `phpunit.xml` dan `tests/bootstrap.php`.

- **Menjalankan Test**:
  ```powershell
  composer test
  ```
- **Lokasi File Test**: `tests/Unit/*Test.php` (namespace `Tests\Unit`).
- **Apa yang Di-test?**:
  Fokuskan Unit Test pada class di dalam `App\Services\`, helper di `app_helper.php`, serta aturan domain/validasi yang memiliki risiko bisnis tinggi.
