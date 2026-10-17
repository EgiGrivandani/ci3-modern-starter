# Panduan kontribusi

Panduan ini ikut dipakai pada repository proyek yang dibuat dari template. Branch, PR, dan pemeriksaan CI dilakukan di repository proyek tersebut.

## Memulai proyek dan setup lokal

Gunakan **Use this template** di GitHub untuk membuat repository proyek baru tanpa riwayat commit starter, lalu clone repository baru itu. Jika memakai clone biasa dari starter, ganti `origin` ke repository proyek sendiri sebelum push (`git remote set-url origin <url-repo-proyek>`); periksa dengan `git remote -v`.

1. Jalankan `composer install` agar versi dependency mengikuti `composer.lock`.
2. Salin `.env.example` menjadi `.env` (`Copy-Item .env.example .env` di PowerShell, atau `cp .env.example .env` di Linux/macOS).
3. Sesuaikan nama aplikasi, URL, `APP_KEY`, dan konfigurasi database lokal. Setiap anggota tim memakai database sendiri. Jangan commit `.env` atau kredensial.
4. Jalankan `composer check`. Arahkan document root web server ke `public/`.
5. Jika menggunakan database, buat database lokal lalu jalankan `composer migrate`.

Contoh CRUD kategori tersedia di `/categories` pada mode development/testing. Sebelum menjadi fitur production, terapkan autentikasi dan otorisasi proyek. Detail setup dan batasan tersedia di [README](README.md).

## Alur kerja sehari-hari

1. Buat branch per tugas, misalnya `feat/category-search` atau `fix/category-validation`.
2. Ikuti pola contoh yang sudah ada dan [CODING_STANDARD.md](CODING_STANDARD.md). Tambahkan tes untuk perubahan perilaku yang penting.
3. Sebelum push, jalankan:

   ```sh
   composer format
   composer format:check
   composer test
   ```

4. Buka PR di repository proyek. Jelaskan masalah, perubahan perilaku, cara pengujian, dan kebutuhan migrasi jika ada.
5. Tunggu pemeriksaan CI lulus dan review rekan tim, kemudian merge.

Pemilik repository dapat mengaktifkan branch protection/ruleset untuk branch utama dan mewajibkan check `PHP 8.0`, `PHP 8.3`, `PHP 8.5` serta review. Pengaturan repository ini tidak otomatis ikut dari template.

## Format kode

PHP CS Fixer dipasang sebagai dependency development dan dikunci melalui `composer.lock`. Konfigurasi bersama ada di `.php-cs-fixer.dist.php`: aturan dasar PSR-12 dengan penyesuaian indentasi tab sesuai proyek, array pendek, dan newline LF. Ini bukan klaim kepatuhan PSR-12 penuh karena standar tersebut memakai spasi.

Formatter mencakup controller, core aplikasi, model, service, exception, helper, migration, scripts, tests, serta `public/index.php`. Core framework `system/`, `vendor/`, file konfigurasi bawaan CI, dan view campuran HTML/PHP tidak diformat otomatis. Tambahkan folder kode aplikasi baru ke finder jika diperlukan. Jangan mengedit dependency atau core framework untuk merapikan format.

`composer format` mengubah file; `composer format:check` hanya memeriksa dan menghasilkan exit code gagal jika masih ada perbedaan. CI memeriksa format, tidak membuat commit perbaikan otomatis.

## Database dan dependency

- Migrasi yang sudah diterapkan pada database bersama tidak boleh diedit atau dihapus. Buat migrasi baru dengan timestamp unik untuk perubahan berikutnya.
- Uji migrasi maju dan rollback di database lokal yang dapat dibuang. Jangan menjalankan `migrate version 0` pada database bersama karena perintah itu me-rollback seluruh migrasi.
- Commit `composer.json` dan `composer.lock` bersama ketika mengubah dependency. Anggota tim lain memakai `composer install`; `composer update` dilakukan sengaja untuk pembaruan dependency.
- Jika menambahkan konfigurasi, perbarui `.env.example` tanpa nilai rahasia dan tambahkan penjelasan bila diperlukan.

## Pemeriksaan otomatis

`.github/workflows/ci.yml` berjalan saat push, PR, atau dijalankan manual. Workflow generik ini tidak bergantung pada nama repository starter, tidak membutuhkan secrets, tidak melakukan deploy, dan ikut bekerja di repository turunan yang mengizinkan GitHub Actions.

CI memvalidasi Composer, memasang dependency dari lock file, menjalankan `composer check`, memeriksa format, dan menjalankan tes pada PHP 8.0, 8.3, dan 8.5. Sesuaikan matrix jika proyek memilih versi PHP tertentu. Matrix adalah target pengujian; status run CI menunjukkan apakah kombinasi tersebut benar-benar lulus.

Tes CRUD memakai SQLite sementara dan server HTTP lokal; ekstensi `sqlite3` serta fungsi `proc_open` harus tersedia. CI tidak menguji server MySQL atau Redis/Memcached/APCu nyata. Tetap uji integrasi layanan yang digunakan proyek sebelum deploy.
