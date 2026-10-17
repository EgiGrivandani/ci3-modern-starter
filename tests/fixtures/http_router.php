<?php

// cli-server may omit process variables from $_SERVER/$_ENV (variables_order).
// Pin test settings before Dotenv loads the developer's local configuration.
foreach (['APP_ENV', 'APP_URL', 'DB_CONNECTION', 'DB_DATABASE', 'DB_PASSWORD', 'DB_DSN', 'DB_PREFIX', 'CSRF_PROTECTION', 'COOKIE_SECURE'] as $key) {
	$_ENV[$key] = getenv($key);
}

$public = dirname(__DIR__, 2) . '/public';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($public . $path)) return false;
require $public . '/index.php';
