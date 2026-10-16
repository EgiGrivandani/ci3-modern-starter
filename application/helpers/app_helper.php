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
