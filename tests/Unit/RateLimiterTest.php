<?php

namespace Tests\Unit;

use App\Exceptions\RateLimiterUnavailableException;
use App\Exceptions\TooManyRequestsException;
use App\Services\RateLimiterService;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
	private string $directory;

	protected function setUp(): void
	{
		$this->directory = sys_get_temp_dir() . '/ci3-limiter-' . bin2hex(random_bytes(8));
		mkdir($this->directory);
	}

	protected function tearDown(): void
	{
		foreach (glob($this->directory . '/*') as $file) {
			unlink($file);
		}
		rmdir($this->directory);
	}

	public function test_different_users_have_separate_quotas(): void
	{
		$limiter = new RateLimiterService('file', $this->directory);
		$firstUser = $limiter->attempt('create_order:user:1', 1);
		$secondUser = $limiter->attempt('create_order:user:2', 1);

		$this->assertSame(0, $firstUser['remaining']);
		$this->assertSame(0, $secondUser['remaining']);
		$this->expectException(TooManyRequestsException::class);
		$limiter->attempt('create_order:user:1', 1);
	}

	public function test_same_user_shares_quota_across_limiter_instances(): void
	{
		$firstRequest = new RateLimiterService('file', $this->directory);
		$secondRequest = new RateLimiterService('file', $this->directory);

		$this->assertSame(1, $firstRequest->attempt('create_order:user:1', 2)['remaining']);
		$this->assertSame(0, $secondRequest->attempt('create_order:user:1', 2)['remaining']);
		$this->expectException(TooManyRequestsException::class);
		$secondRequest->attempt('create_order:user:1', 2);
	}

	public function test_different_actions_have_separate_quotas_for_the_same_user(): void
	{
		$limiter = new RateLimiterService('file', $this->directory);
		$this->assertSame(0, $limiter->attempt('create_order:user:1', 1)['remaining']);
		$this->assertSame(0, $limiter->attempt('export_order:user:1', 1)['remaining']);
	}

	public function test_unwritable_storage_stops_every_attempt(): void
	{
		file_put_contents($this->directory . '/blocker', 'file');
		$limiter = new RateLimiterService('file', $this->directory . '/blocker/child');
		for ($i = 0; $i < 3; $i++) {
			try {
				$limiter->attempt('login', 1);
				$this->fail('Storage failure must not allow a request.');
			} catch (RateLimiterUnavailableException $e) {
				$this->assertSame(503, $e->getStatusCode());
				$this->assertSame('RATE_LIMITER_UNAVAILABLE', $e->getErrorCode());
			}
		}
	}

	public function test_corrupt_file_does_not_reset_the_limit(): void
	{
		file_put_contents($this->directory . '/rl_' . md5('login') . '.json', '{broken');
		$this->expectException(RateLimiterUnavailableException::class);
		(new RateLimiterService('file', $this->directory))->attempt('login');
	}

	public function test_expiry_and_clear_start_a_new_window(): void
	{
		$path = $this->directory . '/rl_' . md5('login') . '.json';
		file_put_contents($path, json_encode(['attempts' => 99, 'reset_at' => time() - 1]));
		$limiter = new RateLimiterService('file', $this->directory);
		$this->assertSame(1, $limiter->attempt('login', 1)['attempts']);
		$limiter->clear('login');
		$this->assertFileExists($path);
		$this->assertSame(1, $limiter->attempt('login', 1)['attempts']);
	}

	public function test_parallel_processes_share_exactly_one_limit(): void
	{
		if (!function_exists('proc_open')) {
			$this->markTestSkipped('proc_open is required for concurrency testing.');
		}
		$workers = [];
		$gate = $this->directory . '/start';
		try {
			for ($i = 0; $i < 6; $i++) {
				$process = proc_open(
					[PHP_BINARY, dirname(__DIR__) . '/fixtures/rate_limit_worker.php', $this->directory, $gate],
					[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
					$pipes
				);
				$this->assertIsResource($process);
				fclose($pipes[0]);
				$workers[] = [$process, $pipes];
			}
			file_put_contents($gate, 'start');
			$allowed = 0;
			foreach ($workers as [$process, $pipes]) {
				$output = stream_get_contents($pipes[1]);
				$error = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				$this->assertSame(0, proc_close($process), $error);
				$this->assertMatchesRegularExpression('/^\d+$/', $output);
				$allowed += (int) $output;
			}
			$this->assertSame(10, $allowed, 'Only 10 of 120 concurrent attempts may pass.');
			$state = json_decode(file_get_contents($this->directory . '/rl_' . md5('parallel') . '.json'), true);
			$this->assertSame(120, $state['attempts']);
		} finally {
			foreach ($workers as [$process, $pipes]) {
				if (is_resource($process)) {
					proc_terminate($process);
					foreach ($pipes as $pipe) {
						if (is_resource($pipe)) {
							fclose($pipe);
						}
					}
					proc_close($process);
				}
			}
		}
	}

	public function test_cache_initialization_race_keeps_the_winning_counter(): void
	{
		$cache = new RateLimitCacheDouble();
		$limiter = $this->cacheLimiter($cache);
		$cache->beforeWindowAdd = function () use ($limiter): void {
			$this->assertSame(1, $limiter->attempt('login', 2)['attempts']);
		};
		$this->assertSame(2, $limiter->attempt('login', 2)['attempts']);
		$this->expectException(TooManyRequestsException::class);
		$limiter->attempt('login', 2);
	}

	public function test_cache_failures_do_not_fall_back_to_file(): void
	{
		foreach (['get', 'add', 'increment'] as $operation) {
			$cache = new RateLimitCacheDouble();
			$cache->failure = $operation;
			try {
				$this->cacheLimiter($cache)->attempt('login', 1);
				$this->fail('Cache failure must stop the request.');
			} catch (RateLimiterUnavailableException $e) {
				$this->assertSame(503, $e->getStatusCode());
			}
		}
		$this->assertSame([], glob($this->directory . '/*'));
	}

	public function test_cache_clear_changes_window_without_reusing_old_counter(): void
	{
		$limiter = $this->cacheLimiter(new RateLimitCacheDouble());
		$this->assertSame(1, $limiter->attempt('login', 1)['attempts']);
		$limiter->clear('login');
		$this->assertSame(1, $limiter->attempt('login', 1)['attempts']);
	}

	private function cacheLimiter(object $cache): RateLimiterService
	{
		return new class ($cache, $this->directory) extends RateLimiterService {
			private object $cache;
			public function __construct(object $cache, string $directory)
			{
				parent::__construct('redis', $directory);
				$this->cache = $cache;
			}
			protected function getAtomicCache(): object
			{
				return $this->cache;
			}
		};
	}
}

/** Deterministic interleaving and failure injection; not a substitute for backend integration tests. */
class RateLimitCacheDouble
{
	public array $values = [];
	public string $failure = '';
	public $beforeWindowAdd;
	public function get(string $key)
	{
		if ($this->failure === 'get') {
			throw new \RuntimeException('Cache offline');
		}
		return $this->values[$key] ?? false;
	}
	public function add(string $key, $value, int $ttl): bool
	{
		if ($this->failure === 'add') {
			return false;
		}
		if (str_ends_with($key, ':window') && $this->beforeWindowAdd !== null) {
			$callback = $this->beforeWindowAdd;
			$this->beforeWindowAdd = null;
			$callback();
		}
		if (array_key_exists($key, $this->values)) {
			return false;
		}
		$this->values[$key] = $value;
		return true;
	}
	public function increment(string $key)
	{
		if ($this->failure === 'increment') {
			return false;
		}
		return ++$this->values[$key];
	}
	public function delete(string $key): void
	{
		unset($this->values[$key]);
	}
}
