<?php

namespace App\Services;

/** Read-only setup diagnostics. No database connection, migration, or secret output. */
class ConfigurationCheck
{
	/** @return array<int, array{status: string, message: string}> */
	public function inspect(string $root, array $settings): array
	{
		return array_merge(
			$this->checkRuntime($root, $settings),
			$this->checkApplication($settings),
			$this->checkSecurity($settings),
			$this->checkDirectories($root),
			$this->checkCache($root, $settings),
			$this->checkDatabase($settings)
		);
	}

	private function checkRuntime(string $root, array $settings): array
	{
		$environment = $settings['APP_ENV'] ?? '';
		$validEnvironment = in_array($environment, ['development', 'testing', 'production'], true);
		$supportedPhpVersion = PHP_VERSION_ID >= 80512 && PHP_VERSION_ID < 80600;

		$checks = [];
		$checks[] = $this->result($validEnvironment, 'APP_ENV harus development, testing, atau production.');
		$checks[] = $this->result($supportedPhpVersion, 'PHP harus seri 8.5 dengan patch minimal 8.5.12, sesuai composer.json.');

		foreach (['json', 'mbstring', 'session'] as $extension) {
			$checks[] = $this->result(extension_loaded($extension), 'Ekstensi PHP ' . $extension . ' harus aktif.');
		}

		$envFileExists = is_file($root . '/.env');
		$checks[] = $this->result($envFileExists, 'File .env tersedia (boleh diganti environment server).', 'WARN');

		return $checks;
	}

	private function checkApplication(array $settings): array
	{
		$name = $settings['APP_NAME'] ?? '';
		$validName = is_string($name) && trim($name) !== '';
		$url = $settings['APP_URL'] ?? '';
		$timezone = $settings['APP_TIMEZONE'] ?? '';
		$validTimezone = in_array($timezone, timezone_identifiers_list(), true);

		return [
			$this->result($validName, 'APP_NAME wajib diisi.'),
			$this->result($this->isHttpUrl($url), 'APP_URL harus URL http/https yang valid.'),
			$this->result($validTimezone, 'APP_TIMEZONE harus nama zona waktu yang valid, misalnya Asia/Jakarta.'),
		];
	}

	private function isHttpUrl($url): bool
	{
		if (!is_string($url)) {
			return false;
		}

		if (filter_var($url, FILTER_VALIDATE_URL) === false) {
			return false;
		}

		$scheme = parse_url($url, PHP_URL_SCHEME);
		return in_array($scheme, ['http', 'https'], true);
	}

	private function checkSecurity(array $settings): array
	{
		$isProduction = ($settings['APP_ENV'] ?? '') === 'production';
		$failureStatus = 'WARN';
		if ($isProduction) {
			$failureStatus = 'FAIL';
		}

		$key = $settings['APP_KEY'] ?? '';
		$validKey = is_string($key) && strlen($key) >= 32;
		$checks = [];
		$checks[] = $this->result(
			$validKey,
			'APP_KEY perlu minimal 32 karakter acak; buat dengan bin2hex(random_bytes(32)).',
			$failureStatus
		);

		foreach (['CSRF_PROTECTION', 'COOKIE_SECURE', 'TRUST_CLOUDFLARE'] as $flag) {
			$isBoolean = is_bool($settings[$flag] ?? null);
			$checks[] = $this->result($isBoolean, $flag . ' harus true atau false.');
		}

		$csrfEnabled = ($settings['CSRF_PROTECTION'] ?? null) === true;
		$checks[] = $this->result($csrfEnabled, 'Aktifkan CSRF_PROTECTION untuk aplikasi berbasis session.', $failureStatus);

		if (!$isProduction) {
			$checks[] = $this->result(false, 'Mode development/testing aktif; gunakan APP_ENV=production saat deploy.', 'WARN');
			return $checks;
		}

		$secureCookie = ($settings['COOKIE_SECURE'] ?? null) === true;
		$url = $settings['APP_URL'] ?? '';
		$usesHttps = is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https';
		$checks[] = $this->result($secureCookie, 'Production membutuhkan COOKIE_SECURE=true.');
		$checks[] = $this->result($usesHttps, 'Production membutuhkan APP_URL dengan HTTPS.');

		return $checks;
	}

	private function checkDirectories(string $root): array
	{
		$directories = [
			'application/cache',
			'application/cache/sessions',
			'application/logs',
			'storage/private',
			'public/uploads',
		];

		$checks = [];
		foreach ($directories as $directory) {
			$path = $root . '/' . $directory;
			$isWritableDirectory = is_dir($path) && is_writable($path);
			$checks[] = $this->result($isWritableDirectory, $directory . ' harus tersedia dan writable oleh pengguna PHP.');
		}

		return $checks;
	}

	private function checkCache(string $root, array $settings): array
	{
		$driver = $settings['CACHE_DRIVER'] ?? 'file';
		$rateLimitDirectory = $root . '/application/cache/ratelimit';
		$checks = [];

		if ($driver === 'file' && file_exists($rateLimitDirectory)) {
			$isWritableDirectory = is_dir($rateLimitDirectory) && is_writable($rateLimitDirectory);
			$checks[] = $this->result($isWritableDirectory, 'application/cache/ratelimit harus berupa direktori writable.');
		}

		$supportedDriver = in_array($driver, ['file', 'redis', 'memcached', 'apcu'], true);
		$checks[] = $this->result($supportedDriver, 'CACHE_DRIVER harus file, redis, memcached, atau apcu.');

		$needsExtension = in_array($driver, ['redis', 'memcached', 'apcu'], true);
		if ($needsExtension) {
			$checks[] = $this->result(extension_loaded($driver), 'Aktifkan ekstensi PHP ' . $driver . ' untuk rate limiter.');
			$checks[] = $this->result(false, 'Ketersediaan layanan/segmen cache perlu diuji pada runtime web; check ini tidak menghubunginya.', 'WARN');
		}

		return $checks;
	}

	private function checkDatabase(array $settings): array
	{
		if (empty($settings['DB_DATABASE'])) {
			return [
				$this->result(false, 'DB_DATABASE belum diisi; isi sebelum memakai CRUD kategori atau migrasi.', 'WARN'),
			];
		}

		$driver = $settings['DB_CONNECTION'] ?? 'mysqli';
		$extensions = [
			'mysqli' => 'mysqli',
			'sqlite3' => 'sqlite3',
			'postgre' => 'pgsql',
			'pdo' => 'pdo',
			'sqlsrv' => 'sqlsrv',
		];
		$extensionAvailable = isset($extensions[$driver]) && extension_loaded($extensions[$driver]);

		return [
			$this->result($extensionAvailable, 'Pastikan ekstensi database untuk DB_CONNECTION aktif (mysqli/sqlite3/postgre/pdo/sqlsrv).'),
		];
	}

	/** @return array{status: string, message: string} */
	private function result(bool $valid, string $message, string $failureStatus = 'FAIL'): array
	{
		$status = $failureStatus;
		if ($valid) {
			$status = 'OK';
		}

		return [
			'status' => $status,
			'message' => $message,
		];
	}
}
