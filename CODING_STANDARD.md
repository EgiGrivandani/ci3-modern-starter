# Standar Programming & Arsitektur (Engineering Standard)

Dokumen ini adalah panduan baku (**Engineering Standard**) untuk seluruh developer yang membangun aplikasi menggunakan **CodeIgniter 3 Modern Starter**. Standar ini memadukan **6 Filosofi Penulisan Kode Pragmatis** dengan **3 Pilar Sistem & Data dari DDIA (*Designing Data-Intensive Applications* — Martin Kleppmann)**.

---

## Daftar Isi
1. [Enam Prinsip Utama Penulisan Kode (Code Craftsmanship)](#1-enam-prinsip-utama-penulisan-kode-code-craftsmanship)
2. [Tiga Pilar Sistem & Data (Prinsip DDIA)](#2-tiga-pilar-sistem--data-prinsip-ddia)
3. [Arsitektur Layering & Aturan Kapan Pakai Service](#3-arsitektur-layering--aturan-kapan-pakai-service)
4. [Standar Penamaan (Naming Conventions)](#4-standar-penamaan-naming-conventions)
5. [Standar Keandalan Data & Transaksi (Reliability)](#5-standar-keandalan-data--transaksi-reliability)
6. [Standar Skalabilitas & Efisiensi Query (Scalability)](#6-standar-skalabilitas--efisiensi-query-scalability)
7. [Standar API Response, Error Handling & Observability (Maintainability)](#7-standar-api-response-error-handling--observability-maintainability)
8. [Standar Keamanan (Security Baseline)](#8-standar-keamanan-security-baseline)
9. [Standar Pengujian (Unit Testing)](#9-standar-pengujian-unit-testing)

---

## 1. Enam Prinsip Utama Penulisan Kode (Code Craftsmanship)

Sebelum menulis kode, setiap programmer wajib memegang 6 prinsip dasar berikut:

### 1. Clarity over Cleverness — *Kejelasan di Atas Kepintaran*
Jangan membuat kode yang terlihat "canggih" atau singkat tetapi sulit dipahami. Pilih implementasi yang langsung dimengerti oleh programmer lain dalam sekali baca.

```php
// ❌ JANGAN (Terlalu "pintar" dengan nested ternary yang membingungkan)
$discount = $user->is_vip ? ($order->total > 1000000 ? 0.25 : ($order->total > 500000 ? 0.15 : 0.05)) : 0;

// ✅ LAKUKAN (Jelas, eksplisit, dan mudah di-debug)
$discount = 0.0;
if ($user->is_vip) {
    if ($order->total > 1_000_000) {
        $discount = 0.25;
    } elseif ($order->total > 500_000) {
        $discount = 0.15;
    } else {
        $discount = 0.05;
    }
}
```

### 2. Simplicity over Complexity — *Utamakan Kesederhanaan*
Jangan memperkenalkan *design pattern*, abstraksi berlapis, atau dependency baru sebelum benar-benar dibutuhkan (*YAGNI — You Aren't Gonna Need It*).
- Jika sebuah fitur hanya melakukan CRUD sederhana pada 1 tabel master (misal tabel `categories`), **Controller boleh langsung memanggil Model** tanpa perlu membuat class `CategoryService` kosong yang hanya meneruskan fungsi.

### 3. Errors Are Values — *Tangani Error Secara Eksplisit*
Error dan kegagalan operasi adalah hasil yang harus diperiksa. Jangan mengabaikan kembalian `null` / `false` hanya karena program berjalan normal saat diuji di lokal, dan **dilarang keras membuat blok `catch` kosong**.

```php
// ❌ JANGAN (Mengabaikan potensi data tidak ditemukan atau menelan exception)
$user = $this->userModel->find($id);
$this->userModel->update($id, ['name' => $newName]);

// ✅ LAKUKAN (Periksa hasil operasi secara eksplisit)
$user = $this->userModel->find($id);
if (!$user) {
    throw new \App\Exceptions\NotFoundException('Data pengguna tidak ditemukan.');
}

$updated = $this->userModel->update($id, ['name' => $newName]);
if (!$updated) {
    throw new \App\Exceptions\AppException('Gagal memperbarui data pengguna.', 500, 'UPDATE_FAILED');
}
```

### 4. Readability Matters — *Kode Ditulis untuk Dibaca Manusia*
Gunakan penamaan yang deskriptif, fungsi dengan satu tanggung jawab yang koheren, dan gunakan **Guard Clauses (*Early Return*)** agar struktur kontrol tidak bersarang terlalu dalam (*maksimal 2–3 level indentasi*).

```php
// ❌ JANGAN (Arrow Anti-Pattern: if-else bersarang dalam)
public function approveOrder(int $orderId): void
{
    $order = $this->orderModel->find($orderId);
    if ($order) {
        if ($order->status === 'pending') {
            if ($order->total > 0) {
                $this->orderModel->update($orderId, ['status' => 'approved']);
            }
        }
    }
}

// ✅ LAKUKAN (Guard Clauses / Early Return: rata kiri dan jelas)
public function approveOrder(int $orderId): void
{
    $order = $this->orderModel->find($orderId);
    if (!$order) {
        throw new \App\Exceptions\NotFoundException('Pesanan tidak ditemukan.');
    }

    if ($order->status !== 'pending') {
        throw new \App\Exceptions\AppException('Hanya pesanan berstatus pending yang dapat disetujui.', 422);
    }

    $this->orderModel->update($orderId, ['status' => 'approved']);
}
```

### 5. Follow Conventions — *Ikuti Standar Ekosistem*
Gunakan struktur folder, base class (`MY_Controller`, `MY_Model`), dan helper global (`app_helper.php`) yang sudah disediakan oleh starter ini. Hindari menciptakan gaya penulisan, struktur response JSON, atau sistem pembungkus sendiri tanpa alasan kuat.

### 6. Design for Maintainability — *Pikirkan Pemeliharaan Sejak Awal*
Kode harus mudah dimodifikasi, diuji (*unit-testable*), didiagnosis saat gagal di production (melalui `request_id()` dan `log_context()`), serta dapat dikembangkan tanpa merusak bagian lain.

---

## 2. Tiga Pilar Sistem & Data (Prinsip DDIA)

Pada level arsitektur sistem dan pengolahan data, setiap fitur wajib memenuhi 3 pilar **DDIA (*Designing Data-Intensive Applications*)**:
1. **Reliability (Keandalan)**: Sistem tetap bekerja benar meskipun terjadi kegagalan di tengah proses atau request bersamaan (*race condition*). Data tidak boleh korup (*half-written data*).
2. **Scalability (Skalabilitas)**: Query yang cepat saat data berjumlah 100 baris harus tetap aman saat data tumbuh menjadi jutaan baris (pagination dibatasi, pilih kolom spesifik, gunakan *batch insert*).
3. **Maintainability & Operability**: Saat terjadi error di production, tim harus bisa melacak penyebabnya dalam hitungan detik menggunakan `request_id` dan log terstruktur.

---

## 3. Arsitektur Layering & Aturan Kapan Pakai Service

```text
HTTP Request ──► Controller (HTTP & Validasi) ──► [Service (Opsional: Logika Bisnis)] ──► Model (Query DB)
```

### A. Controller (`application/controllers/`) — *Thin Controller*
- **Tanggung Jawab**: Menerima HTTP request, menjalankan `$this->validate(...)`, memanggil Service/Model, dan mengembalikan View (`$this->renderLayout()`) atau JSON (`$this->success()` / `$this->handleException()`).
- **Dilarang**: Menulis query `$this->db->...` langsung di Controller atau menaruh logika perhitungan bisnis yang panjang.

### B. Service Layer (`application/Services/`) — *Business Logic (PSR-4 `App\Services`)*
- **Kapan WAJIB membuat Service?**:
  1. Ketika proses melibatkan **lebih dari 1 Model/Tabel** (misal: Checkout yang mengubah tabel `orders`, `order_items`, dan `products`).
  2. Ketika ada **aturan/kalkulasi bisnis yang kompleks** (hitung diskon bertingkat, pajak, komisi, dsb.) yang perlu di-*unit test*.
  3. Ketika berintegrasi dengan **API Eksternal** (Payment Gateway, WhatsApp API, SMTP, dsb.).
- **Kapan TIDAK PERLU membuat Service?**:
  - Jika hanya melakukan CRUD sederhana pada 1 tabel tanpa logika bisnis khusus, panggil `Model` langsung dari `Controller` (*Simplicity over Complexity*).

### C. Model Layer (`application/models/`) — *Data Access*
- **Tanggung Jawab**: Mewarisi `MY_Model`, mendefinisikan `$table`, `$fillable`, `$timestamps`, `$softDeletes`, dan membungkus query spesifik tabel tersebut.

---

## 4. Standar Penamaan (Naming Conventions)

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

## 5. Standar Keandalan Data & Transaksi (Reliability)

### A. Gunakan Database Transaction untuk Multi-Query
Setiap operasi yang mengubah **lebih dari 1 tabel** **WAJIB** dibungkus dengan `$this->model->transaction()`:

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

### B. Cegah Race Condition dengan Pessimistic Locking (`findForUpdate`)
Saat memproses data sensitif seperti **potong stok** atau **potong saldo**, gunakan `findForUpdate()` di dalam `transaction()` untuk mengunci baris hingga transaksi selesai:

```php
$this->walletModel->transaction(function () use ($userId, $nominal) {
    // Mengunci baris (SELECT * FROM wallets WHERE id = ? LIMIT 1 FOR UPDATE)
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
Pada tabel yang memiliki nilai histori/audit (`users`, `orders`, `invoices`), aktifkan `$softDeletes = true;` di Model dan sediakan kolom `deleted_at DATETIME NULL` di tabel database.

---

## 6. Standar Skalabilitas & Efisiensi Query (Scalability)

1. **Hindari `all()` pada Tabel Transaksi Besar**:
   Gunakan `paginate()` untuk menampilkan daftar data. `MY_Model::paginate()` otomatis membatasi jumlah maksimal baris per halaman (`$maxPerPage = 100`).
2. **Pilih Kolom Spesifik (`$columns`)**:
   Jangan mengambil seluruh kolom (`SELECT *`) jika hanya butuh beberapa kolom:
   ```php
   $users = $this->userModel->all(['is_active' => 1], 'name ASC', ['id', 'name', 'email']);
   ```
3. **Gunakan `insertBatch()` untuk Insert Banyak Baris**:
   Jangan memanggil `$model->create()` di dalam *loop* `foreach`. Gunakan `$model->insertBatch($rows)`.

---

## 7. Standar API Response, Error Handling & Observability (Maintainability)

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
Gunakan `log_context($level, $message, $context)` agar setiap catatan log di `application/logs/` otomatis memiliki `request_id` yang sama dengan header `X-Request-Id` dan response JSON pengunjung:

```php
log_context('error', 'Gagal memproses pembayaran Midtrans', [
    'order_id'  => $orderId,
    'http_code' => 504,
]);
```

---

## 8. Standar Keamanan (Security Baseline)

1. **Anti Mass-Assignment**: Setiap Model **wajib** mendefinisikan `protected array $fillable = [...]`.
2. **Anti XSS di View**: Setiap pencetakan variabel di file `.php` view **wajib** dibungkus fungsi `<?= e($var) ?>`.
3. **Proteksi CSRF**: Setiap `<form method="POST">` HTML **wajib** menyertakan `<?= csrf_field() ?>`.
4. **Password Hashing**: Wajib menggunakan `password_hash($plain, PASSWORD_BCRYPT)` dan `password_verify($plain, $hash)`.

---

## 9. Standar Pengujian (Unit Testing)

Starter ini dilengkapi dengan konfigurasi **PHPUnit** di `phpunit.xml` dan `tests/bootstrap.php`.

- **Menjalankan Test**:
  ```powershell
  composer test
  ```
- **Lokasi File Test**: `tests/Unit/*Test.php` (namespace `Tests\Unit`).
- **Fokus Pengujian**: Utamakan menulis Unit Test untuk logika bisnis di `App\Services\`, helper di `app_helper.php`, dan validasi aturan domain yang berisiko tinggi.
