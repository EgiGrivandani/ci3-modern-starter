<?php

namespace App\Services;

/** Read-only setup diagnostics. No database connection, migration, or secret output. */
class ConfigurationCheck
{
	/** @return array<int, array{status: string, message: string}> */
	public function inspect(string $root, array $settings): array
	{
		$checks = [];
		$check = function (bool $valid, string $message, bool $warning = false) use (&$checks): void {
			$checks[] = ['status' => $valid ? 'OK' : ($warning ? 'WARN' : 'FAIL'), 'message' => $message];
		};
		$environment = $settings['APP_ENV'] ?? '';
		$production = $environment === 'production';
		$check(in_array($environment, ['development', 'testing', 'production'], true), 'APP_ENV harus development, testing, atau production.');
		$check(PHP_VERSION_ID >= 80000, 'PHP minimal 8.0; gunakan versi yang diuji proyek ini.');
		foreach (['json', 'mbstring', 'session'] as $extension) {
			$check(extension_loaded($extension), 'Ekstensi PHP ' . $extension . ' harus aktif.');
		}
		$check(is_file($root . '/.env'), 'File .env tersedia (boleh diganti environment server).', true);
		$check(is_string($settings['APP_NAME'] ?? null) && trim($settings['APP_NAME']) !== '', 'APP_NAME wajib diisi.');
		$url = $settings['APP_URL'] ?? '';
		$check(is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false
			&& in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true), 'APP_URL harus URL http/https yang valid.');
		$check(in_array($settings['APP_TIMEZONE'] ?? '', timezone_identifiers_list(), true), 'APP_TIMEZONE harus nama zona waktu yang valid, misalnya Asia/Jakarta.');
		$key = $settings['APP_KEY'] ?? '';
		$check(is_string($key) && strlen($key) >= 32, 'APP_KEY perlu minimal 32 karakter acak; buat dengan bin2hex(random_bytes(32)).', !$production);
		foreach (['CSRF_PROTECTION', 'COOKIE_SECURE', 'TRUST_CLOUDFLARE'] as $flag) {
			$check(is_bool($settings[$flag] ?? null), $flag . ' harus true atau false.');
		}
		$check(($settings['CSRF_PROTECTION'] ?? null) === true, 'Aktifkan CSRF_PROTECTION untuk aplikasi berbasis session.', !$production);
		if ($production) {
			$check(($settings['COOKIE_SECURE'] ?? null) === true, 'Production membutuhkan COOKIE_SECURE=true.');
			$check(is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https', 'Production membutuhkan APP_URL dengan HTTPS.');
		} else {
			$check(false, 'Mode development/testing aktif; gunakan APP_ENV=production saat deploy.', true);
		}
		foreach (['application/cache', 'application/cache/sessions', 'application/logs', 'storage/private', 'public/uploads'] as $directory) {
			$check(is_dir($root . '/' . $directory) && is_writable($root . '/' . $directory), $directory . ' harus tersedia dan writable oleh pengguna PHP.');
		}
		$driver = $settings['CACHE_DRIVER'] ?? 'file';
		if ($driver === 'file' && file_exists($root . '/application/cache/ratelimit')) {
			$check(is_dir($root . '/application/cache/ratelimit') && is_writable($root . '/application/cache/ratelimit'), 'application/cache/ratelimit harus berupa direktori writable.');
		}
		$check(in_array($driver, ['file', 'redis', 'memcached', 'apcu'], true), 'CACHE_DRIVER harus file, redis, memcached, atau apcu.');
		if (in_array($driver, ['redis', 'memcached', 'apcu'], true)) {
			$check(extension_loaded($driver), 'Aktifkan ekstensi PHP ' . $driver . ' untuk rate limiter.');
			$check(false, 'Ketersediaan layanan/segmen cache perlu diuji pada runtime web; check ini tidak menghubunginya.', true);
		}
		$db = $settings['DB_CONNECTION'] ?? 'mysqli';
		$extensions = ['mysqli' => 'mysqli', 'sqlite3' => 'sqlite3', 'postgre' => 'pgsql', 'pdo' => 'pdo', 'sqlsrv' => 'sqlsrv'];
		if (!empty($settings['DB_DATABASE'])) {
			$check(isset($extensions[$db]) && extension_loaded($extensions[$db]), 'Pastikan ekstensi database untuk DB_CONNECTION aktif (mysqli/sqlite3/postgre/pdo/sqlsrv).');
		} else {
			$check(false, 'DB_DATABASE belum diisi; isi sebelum memakai CRUD kategori atau migrasi.', true);
		}
		return $checks;
	}
}
