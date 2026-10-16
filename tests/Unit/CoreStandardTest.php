<?php

namespace Tests\Unit;

use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\TooManyRequestsException;
use App\Exceptions\ValidationException;
use App\Services\AppInfoService;
use App\Services\RateLimiterService;
use PHPUnit\Framework\TestCase;
use User_model;

require_once APPPATH . 'models/User_model.php';

class CoreStandardTest extends TestCase
{
	public function test_env_helper_parses_types_and_defaults(): void
	{
		$_ENV['TEST_BOOL_TRUE']  = 'true';
		$_ENV['TEST_BOOL_FALSE'] = 'false';
		$_ENV['TEST_NULL']       = 'null';
		$_ENV['TEST_EMPTY']      = 'empty';

		$this->assertTrue(env('TEST_BOOL_TRUE'));
		$this->assertFalse(env('TEST_BOOL_FALSE'));
		$this->assertNull(env('TEST_NULL'));
		$this->assertSame('', env('TEST_EMPTY'));
		$this->assertSame('fallback', env('NON_EXISTENT_ENV_KEY_XYZ', 'fallback'));
	}

	public function test_html_escape_helper_prevents_xss(): void
	{
		$malicious = '<script>alert("xss")</script>';
		$this->assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', e($malicious));
		$this->assertSame('', e(null));
	}

	public function test_request_id_is_consistent_within_request(): void
	{
		$first  = request_id();
		$second = request_id();

		$this->assertNotEmpty($first);
		$this->assertSame($first, $second);
	}

	public function test_app_info_service_returns_complete_summary(): void
	{
		$service = new AppInfoService();
		$summary = $service->getSummary();

		$this->assertIsArray($summary);
		$this->assertArrayHasKey('app_name', $summary);
		$this->assertArrayHasKey('environment', $summary);
		$this->assertArrayHasKey('php_version', $summary);
		$this->assertArrayHasKey('ci_version', $summary);
		$this->assertArrayHasKey('request_id', $summary);
		$this->assertSame(PHP_VERSION, $summary['php_version']);
	}

	public function test_domain_exceptions_carry_status_and_error_codes(): void
	{
		$validationEx = new ValidationException(['email' => 'Email wajib diisi.']);
		$this->assertInstanceOf(AppException::class, $validationEx);
		$this->assertSame(422, $validationEx->getStatusCode());
		$this->assertSame('VALIDATION_ERROR', $validationEx->getErrorCode());
		$this->assertSame(['email' => 'Email wajib diisi.'], $validationEx->getErrors());

		$notFoundEx = new NotFoundException('User tidak ditemukan.');
		$this->assertSame(404, $notFoundEx->getStatusCode());
		$this->assertSame('RESOURCE_NOT_FOUND', $notFoundEx->getErrorCode());
		$this->assertSame('User tidak ditemukan.', $notFoundEx->getMessage());
	}

	public function test_model_fillable_protection_and_pagination_clamping(): void
	{
		$userModel = new User_model();

		// Mass-assignment protection ($fillable)
		$filtered = $userModel->filterFillable([
			'name'               => 'Budi',
			'email'              => 'budi@example.com',
			'is_superadmin_hack' => 1, // Should be stripped out
		]);

		$this->assertSame([
			'name'  => 'Budi',
			'email' => 'budi@example.com',
		], $filtered);
		$this->assertArrayNotHasKey('is_superadmin_hack', $filtered);

		// Pagination clamping (DDIA Scalability)
		$this->assertSame(1, $userModel->clampPerPage(-5));
		$this->assertSame(25, $userModel->clampPerPage(25));
		$this->assertSame(100, $userModel->clampPerPage(999999));
	}

	public function test_rate_limiter_enforces_limits_and_resets(): void
	{
		$limiter = new RateLimiterService('file');
		$key     = 'test_login:127.0.0.1';
		$limiter->clear($key);

		// Attempt 1 & 2 (allowed, max 2)
		$first = $limiter->attempt($key, 2, 60);
		$this->assertSame(1, $first['attempts']);
		$this->assertSame(1, $first['remaining']);

		$second = $limiter->attempt($key, 2, 60);
		$this->assertSame(2, $second['attempts']);
		$this->assertSame(0, $second['remaining']);

		// Attempt 3 should throw TooManyRequestsException (HTTP 429)
		try {
			$limiter->attempt($key, 2, 60);
			$this->fail('Expected TooManyRequestsException was not thrown.');
		} catch (TooManyRequestsException $e) {
			$this->assertSame(429, $e->getStatusCode());
			$this->assertSame('TOO_MANY_REQUESTS', $e->getErrorCode());
			$this->assertGreaterThan(0, $e->getRetryAfter());
		} finally {
			$limiter->clear($key);
		}
	}
}
