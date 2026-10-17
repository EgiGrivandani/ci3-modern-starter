<?php

$root = dirname(__DIR__);
if (!is_file($root . '/vendor/autoload.php')) {
	fwrite(STDERR, '[FAIL] Jalankan composer install terlebih dahulu.' . PHP_EOL);
	exit(1);
}
require $root . '/vendor/autoload.php';

try {
	Dotenv\Dotenv::createImmutable($root)->safeLoad();
} catch (Throwable $e) {
	// Dotenv error messages can include secret values from the offending line.
	fwrite(STDERR, '[FAIL] Format .env tidak valid. Periksa penulisan key, value, dan tanda kutip.' . PHP_EOL);
	exit(1);
}
$defaults = [
	'APP_ENV' => 'development',
	'APP_NAME' => '',
	'APP_URL' => '',
	'APP_TIMEZONE' => 'Asia/Jakarta',
	'APP_KEY' => '',
	'CSRF_PROTECTION' => true,
	'COOKIE_SECURE' => false,
	'TRUST_CLOUDFLARE' => false,
	'CACHE_DRIVER' => 'file',
	'DB_CONNECTION' => 'mysqli',
	'DB_DATABASE' => '',
];
$settings = [];
foreach ($defaults as $name => $default) {
	$settings[$name] = env($name, $default);
}
$checker = new App\Services\ConfigurationCheck();
$checks = $checker->inspect($root, $settings);
$hasFailures = false;

foreach ($checks as $check) {
	echo '[' . $check['status'] . '] ' . $check['message'] . PHP_EOL;
	if ($check['status'] === 'FAIL') {
		$hasFailures = true;
	}
}

echo PHP_EOL . 'Pemeriksaan memakai PHP CLI dan pengguna terminal. Tidak menguji koneksi database, web server, atau kredensial.' . PHP_EOL;
if ($hasFailures) {
	exit(1);
}
exit(0);
