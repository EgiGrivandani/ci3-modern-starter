<?php

/**
 * Global Application Helpers (Laravel-style convenience functions)
 *
 * Automatically loaded by Composer via "autoload.files" in composer.json.
 */

if (!function_exists('env')) {
	/**
	 * Get the value of an environment variable from .env or server environment.
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	function env(string $key, $default = null)
	{
		$value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

		if ($value === false || $value === null) {
			return $default;
		}

		switch (strtolower((string) $value)) {
			case 'true':
			case '(true)':
				return true;
			case 'false':
			case '(false)':
				return false;
			case 'empty':
			case '(empty)':
				return '';
			case 'null':
			case '(null)':
				return null;
		}

		return $value;
	}
}

if (!function_exists('e')) {
	/**
	 * Escape HTML special characters in a string (Anti-XSS helper like Laravel Blade).
	 *
	 * @param mixed $value
	 * @param bool  $doubleEncode
	 * @return string
	 */
	function e($value, bool $doubleEncode = true): string
	{
		if ($value === null) {
			return '';
		}

		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
	}
}

if (!function_exists('asset')) {
	/**
	 * Generate a full URL to a static file inside public/assets/.
	 *
	 * Example: asset('css/app.css') -> http://example.test/assets/css/app.css
	 *
	 * @param string $path
	 * @return string
	 */
	function asset(string $path = ''): string
	{
		return base_url('assets/' . ltrim($path, '/'));
	}
}

if (!function_exists('storage_path')) {
	/**
	 * Get the absolute filesystem path to the private storage directory (outside public/).
	 * Use this for sensitive files (KTP, invoices, private documents) that must not have a direct public URL.
	 *
	 * Example: storage_path('invoices/inv-001.pdf')
	 *
	 * @param string $path
	 * @return string
	 */
	function storage_path(string $path = ''): string
	{
		$base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private';
		if ($path === '') {
			return $base;
		}

		$normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\'));
		return $base . DIRECTORY_SEPARATOR . $normalized;
	}
}

if (!function_exists('upload_path')) {
	/**
	 * Get the absolute filesystem path to the public uploads directory (public/uploads/).
	 * Use this only for public media files (product images, banners, avatars).
	 *
	 * Example: upload_path('products/shoe.jpg')
	 *
	 * @param string $path
	 * @return string
	 */
	function upload_path(string $path = ''): string
	{
		$base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
		if ($path === '') {
			return $base;
		}

		$normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\'));
		return $base . DIRECTORY_SEPARATOR . $normalized;
	}
}

if (!function_exists('upload_url')) {
	/**
	 * Generate a full public URL to an uploaded file inside public/uploads/.
	 *
	 * Example: upload_url('products/shoe.jpg') -> http://example.test/uploads/products/shoe.jpg
	 *
	 * @param string $path
	 * @return string
	 */
	function upload_url(string $path = ''): string
	{
		return base_url('uploads/' . ltrim($path, '/'));
	}
}

if (!function_exists('csrf_field')) {
	/**
	 * Generate a hidden CSRF token input field for HTML forms (like @csrf in Laravel).
	 *
	 * @return string
	 */
	function csrf_field(): string
	{
		$ci =& get_instance();
		if (!isset($ci->security)) {
			return '';
		}

		$name = e($ci->security->get_csrf_token_name());
		$hash = e($ci->security->get_csrf_hash());

		return '<input type="hidden" name="' . $name . '" value="' . $hash . '">';
	}
}

if (!function_exists('request_id')) {
	/**
	 * Get or generate a unique correlation ID for the current request (Observability).
	 *
	 * @return string
	 */
	function request_id(): string
	{
		static $reqId = null;

		if ($reqId !== null) {
			return $reqId;
		}

		$incoming = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
		if (is_string($incoming) && preg_match('/^[a-zA-Z0-9\-_]{8,64}$/', $incoming)) {
			$reqId = $incoming;
			return $reqId;
		}

		try {
			$reqId = bin2hex(random_bytes(8));
		} catch (Throwable $e) {
			$reqId = uniqid('req_', true);
		}

		return $reqId;
	}
}

if (!function_exists('log_context')) {
	/**
	 * Write a structured log entry with automatic request_id correlation and JSON context.
	 *
	 * @param string               $level   'error'|'debug'|'info'
	 * @param string               $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	function log_context(string $level, string $message, array $context = []): void
	{
		if (!function_exists('log_message')) {
			return;
		}

		$payload = array_merge(['request_id' => request_id()], $context);
		$encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		log_message($level, $message . ' | ' . $encoded);
	}
}

if (!function_exists('dump')) {
	/**
	 * Dump the passed variables without stopping script execution.
	 *
	 * @param mixed ...$vars
	 * @return void
	 */
	function dump(...$vars): void
	{
		$isCli = (PHP_SAPI === 'cli' || defined('STDIN'));
		foreach ($vars as $var) {
			if ($isCli) {
				var_dump($var);
			} else {
				echo '<pre style="background:#18171B;color:#FF8400;padding:12px;border-radius:6px;font:13px/1.5 Consolas,monospace;overflow:auto;z-index:99999;position:relative;">';
				var_dump($var);
				echo '</pre>';
			}
		}
	}
}

if (!function_exists('dd')) {
	/**
	 * Dump the passed variables and terminate the script immediately.
	 *
	 * @param mixed ...$vars
	 * @return never
	 */
	function dd(...$vars): never
	{
		dump(...$vars);
		exit(1);
	}
}

