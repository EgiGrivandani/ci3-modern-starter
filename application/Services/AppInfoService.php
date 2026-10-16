<?php

namespace App\Services;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sample PSR-4 Service Class
 *
 * Automatically loaded via Composer PSR-4 ("App\\": "application/")
 * without needing $this->load->library().
 */
class AppInfoService
{
	/**
	 * Return runtime & environment metadata for the application.
	 *
	 * @return array<string, mixed>
	 */
	public function getSummary(): array
	{
		return [
			'app_name'    => env('APP_NAME', 'CI3 Modern Starter'),
			'environment' => ENVIRONMENT,
			'php_version' => PHP_VERSION,
			'ci_version'  => CI_VERSION,
			'timezone'    => date_default_timezone_get(),
			'server_time' => date('Y-m-d H:i:s'),
			'base_url'    => function_exists('base_url') ? base_url() : env('APP_URL', 'http://localhost/'),
			'request_id'  => request_id(),
		];
	}
}
