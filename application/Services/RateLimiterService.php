<?php

namespace App\Services;

use App\Exceptions\TooManyRequestsException;
use Throwable;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Multi-Driver Rate Limiter Service (DDIA Load Shedding & Brute-Force Protection)
 *
 * Supports:
 * - 'file' (default, zero-dependency atomic flock storage in application/cache/ratelimit/)
 * - 'redis', 'memcached', 'apcu' (via CodeIgniter's CI_Cache driver)
 *
 * Automatically degrades gracefully to atomic 'file' driver if the primary cache server
 * (e.g. Redis) is unreachable, ensuring rate limiting stays active without crashing the app.
 */
class RateLimiterService
{
	protected string $driver;
	protected string $storageDir;

	public function __construct(?string $driver = null, ?string $storageDir = null)
	{
		$this->driver     = strtolower($driver ?? (string) env('CACHE_DRIVER', 'file'));
		$this->storageDir = $storageDir ?? (APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'ratelimit');
	}

	/**
	 * Attempt an action for a given key and throw TooManyRequestsException if limit is exceeded.
	 *
	 * @param string $key          Unique action + identifier key (e.g. "login:127.0.0.1")
	 * @param int    $maxAttempts  Maximum allowed attempts within $decaySeconds
	 * @param int    $decaySeconds Time window in seconds
	 * @return array{attempts: int, remaining: int, reset_at: int, retry_after: int}
	 * @throws TooManyRequestsException
	 */
	public function attempt(string $key, int $maxAttempts = 60, int $decaySeconds = 60): array
	{
		$maxAttempts  = max(1, $maxAttempts);
		$decaySeconds = max(1, $decaySeconds);
		$safeKey      = 'rl_' . md5($key);

		$state = $this->hit($safeKey, $decaySeconds);

		$attempts   = $state['attempts'];
		$resetAt    = $state['reset_at'];
		$retryAfter = max(1, $resetAt - time());
		$remaining  = max(0, $maxAttempts - $attempts);

		if ($attempts > $maxAttempts) {
			throw new TooManyRequestsException(
				$retryAfter,
				"Too many attempts. Please try again in {$retryAfter} seconds."
			);
		}

		return [
			'attempts'    => $attempts,
			'remaining'   => $remaining,
			'reset_at'    => $resetAt,
			'retry_after' => $retryAfter,
		];
	}

	/**
	 * Reset rate limit counter for a specific key.
	 *
	 * @param string $key
	 * @return void
	 */
	public function clear(string $key): void
	{
		$safeKey = 'rl_' . md5($key);

		if ($this->driver !== 'file') {
			try {
				$cache = $this->getCiCache();
				if ($cache !== null) {
					$cache->delete($safeKey);
					return;
				}
			} catch (Throwable $e) {
				// Fall through to file cleanup
			}
		}

		$filePath = $this->storageDir . DIRECTORY_SEPARATOR . $safeKey . '.json';
		if (is_file($filePath)) {
			@unlink($filePath);
		}
	}

	/**
	 * Record a hit in the configured storage driver (with automatic safe fallback to file).
	 *
	 * @param string $safeKey
	 * @param int    $decaySeconds
	 * @return array{attempts: int, reset_at: int}
	 */
	protected function hit(string $safeKey, int $decaySeconds): array
	{
		if ($this->driver !== 'file') {
			try {
				$cache = $this->getCiCache();
				if ($cache !== null) {
					return $this->hitViaCacheDriver($cache, $safeKey, $decaySeconds);
				}
			} catch (Throwable $e) {
				log_context('error', 'RateLimiter cache driver failed, falling back to file storage.', [
					'driver' => $this->driver,
					'error'  => $e->getMessage(),
				]);
			}
		}

		return $this->hitViaFile($safeKey, $decaySeconds);
	}

	/**
	 * Store and increment counter using CI_Cache (redis / memcached / apcu).
	 *
	 * @param object $cache
	 * @param string $safeKey
	 * @param int    $decaySeconds
	 * @return array{attempts: int, reset_at: int}
	 */
	protected function hitViaCacheDriver(object $cache, string $safeKey, int $decaySeconds): array
	{
		$now  = time();
		$data = $cache->get($safeKey);

		if (!is_array($data) || !isset($data['attempts'], $data['reset_at']) || $data['reset_at'] <= $now) {
			$state = [
				'attempts' => 1,
				'reset_at' => $now + $decaySeconds,
			];
			$cache->save($safeKey, $state, $decaySeconds);
			return $state;
		}

		$data['attempts'] = (int) $data['attempts'] + 1;
		$ttl = max(1, (int) $data['reset_at'] - $now);
		$cache->save($safeKey, $data, $ttl);

		return [
			'attempts' => $data['attempts'],
			'reset_at' => (int) $data['reset_at'],
		];
	}

	/**
	 * Store and increment counter using atomic file locking (flock).
	 *
	 * @param string $safeKey
	 * @param int    $decaySeconds
	 * @return array{attempts: int, reset_at: int}
	 */
	protected function hitViaFile(string $safeKey, int $decaySeconds): array
	{
		if (!is_dir($this->storageDir)) {
			@mkdir($this->storageDir, 0755, true);
		}

		$filePath = $this->storageDir . DIRECTORY_SEPARATOR . $safeKey . '.json';
		$now      = time();
		$fp       = @fopen($filePath, 'c+');

		if ($fp === false) {
			return ['attempts' => 1, 'reset_at' => $now + $decaySeconds];
		}

		$state = ['attempts' => 1, 'reset_at' => $now + $decaySeconds];

		if (flock($fp, LOCK_EX)) {
			$raw     = stream_get_contents($fp);
			$decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

			if (is_array($decoded) && isset($decoded['attempts'], $decoded['reset_at']) && $decoded['reset_at'] > $now) {
				$state = [
					'attempts' => (int) $decoded['attempts'] + 1,
					'reset_at' => (int) $decoded['reset_at'],
				];
			}

			ftruncate($fp, 0);
			rewind($fp);
			fwrite($fp, (string) json_encode($state));
			fflush($fp);
			flock($fp, LOCK_UN);
		}

		fclose($fp);
		return $state;
	}

	/**
	 * Resolve CodeIgniter's CI_Cache instance if available in HTTP runtime.
	 *
	 * @return object|null
	 */
	protected function getCiCache(): ?object
	{
		if (!function_exists('get_instance')) {
			return null;
		}

		$ci =& get_instance();
		if (!is_object($ci) || !isset($ci->load)) {
			return null;
		}

		if (!isset($ci->cache)) {
			$ci->load->driver('cache', [
				'adapter' => $this->driver,
				'backup'  => 'file',
			]);
		}

		return $ci->cache ?? null;
	}
}
