<?php

namespace Tests\Unit;

use App\Services\ConfigurationCheck;
use PHPUnit\Framework\TestCase;

class ConfigurationCheckTest extends TestCase
{
	private function settings(): array
	{
		return [
			'APP_ENV' => 'production',
			'APP_NAME' => 'Test',
			'APP_URL' => 'https://example.com/',
			'APP_TIMEZONE' => 'Asia/Jakarta',
			'APP_KEY' => str_repeat('test-secret-', 4),
			'CSRF_PROTECTION' => true,
			'COOKIE_SECURE' => true,
			'TRUST_CLOUDFLARE' => false,
			'CACHE_DRIVER' => 'file',
			'DB_CONNECTION' => 'mysqli',
			'DB_DATABASE' => '',
		];
	}

	public function test_valid_configuration_has_no_failures_or_secret_values(): void
	{
		$settings = $this->settings();
		$checker = new ConfigurationCheck();
		$checks = $checker->inspect(dirname(__DIR__, 2), $settings);
		$this->assertSame([], $this->failureMessages($checks));
		$this->assertStringNotContainsString($settings['APP_KEY'], json_encode($checks));
	}

	public function test_unsafe_production_settings_and_invalid_types_fail(): void
	{
		$settings = array_merge($this->settings(), [
			'APP_KEY' => '',
			'APP_URL' => 'http://example.com/',
			'COOKIE_SECURE' => false,
			'CSRF_PROTECTION' => false,
			'APP_TIMEZONE' => 'invalid',
			'TRUST_CLOUDFLARE' => 'maybe',
		]);
		$checker = new ConfigurationCheck();
		$checks = $checker->inspect(dirname(__DIR__, 2), $settings);
		$messages = implode(PHP_EOL, $this->failureMessages($checks));
		foreach (['APP_KEY', 'HTTPS', 'COOKIE_SECURE', 'CSRF_PROTECTION', 'APP_TIMEZONE', 'TRUST_CLOUDFLARE'] as $name) {
			$this->assertStringContainsString($name, $messages);
		}
	}

	public function test_missing_directories_are_reported_without_creating_them(): void
	{
		$root = sys_get_temp_dir() . '/missing-check-' . bin2hex(random_bytes(8));
		$checker = new ConfigurationCheck();
		$checks = $checker->inspect($root, $this->settings());
		$failures = $this->failureMessages($checks);
		$this->assertCount(5, $failures);
		$this->assertDirectoryDoesNotExist($root);
	}

	private function failureMessages(array $checks): array
	{
		$messages = [];
		foreach ($checks as $check) {
			if ($check['status'] === 'FAIL') {
				$messages[] = $check['message'];
			}
		}
		return $messages;
	}
}
