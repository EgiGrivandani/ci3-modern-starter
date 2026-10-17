<?php

namespace App\Services;

use App\Exceptions\TooManyRequestsException;
use App\Exceptions\RateLimiterUnavailableException;
use RuntimeException;
use Throwable;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Fixed-duration windows starting at the first attempt.
 * File storage uses flock; native caches use add-if-absent and atomic increments.
 * Storage failures stop the request with HTTP 503; counters never silently switch stores.
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
		try {
			$safeKey = 'rl_' . md5($key);
			if ($this->driver === 'file') {
				// Keep the same inode: unlinking would split locks held by waiting workers.
				$this->withFileLock($safeKey, function ($fp): void {
					$this->writeState($fp, ['attempts' => 0, 'reset_at' => 0]);
				});
			} else {
				$this->getAtomicCache()->delete($safeKey . ':window');
			}
		} catch (Throwable $e) {
			$this->storageFailure($e);
		}
	}

	protected function hit(string $safeKey, int $decaySeconds): array
	{
		try {
			if ($this->driver !== 'file') {
				return $this->hitViaCacheDriver($this->getAtomicCache(), $safeKey, $decaySeconds);
			}
			return $this->hitViaFile($safeKey, $decaySeconds);
		} catch (Throwable $e) {
			$this->storageFailure($e);
		}
	}

	protected function storageFailure(Throwable $e): void
	{
		log_context('error', 'Rate limiter storage failed.', ['driver' => $this->driver, 'error' => $e->getMessage()]);
		throw new RateLimiterUnavailableException($e);
	}

	protected function hitViaCacheDriver(object $cache, string $safeKey, int $decaySeconds): array
	{
		$windowKey = $safeKey . ':window';
		$raw = $cache->get($windowKey);
		if ($raw === false) {
			// Create the counter BEFORE publishing the window. Only the add winner's
			// token is used; losing candidates expire automatically.
			$state = ['token' => bin2hex(random_bytes(16)), 'reset_at' => time() + $decaySeconds];
			if (!$cache->add($safeKey . ':' . $state['token'], 0, $decaySeconds + 1)) {
				throw new RuntimeException('Cannot initialize rate limit counter.');
			}
			$raw = json_encode($state, JSON_THROW_ON_ERROR);
			if (!$cache->add($windowKey, $raw, $decaySeconds)) {
				$raw = $cache->get($windowKey);
			}
		}
		$state = is_string($raw) ? json_decode($raw, true) : null;
		if (!is_array($state) || !isset($state['token'], $state['reset_at'])
			|| !is_string($state['token']) || !preg_match('/^[a-f0-9]{32}$/D', $state['token'])
			|| !is_int($state['reset_at']) || $state['reset_at'] < 1) {
			throw new RuntimeException('Rate limit window is unavailable or invalid.');
		}
		$attempts = $cache->increment($safeKey . ':' . $state['token']);
		if (!is_int($attempts) || $attempts < 1) {
			throw new RuntimeException('Cannot increment rate limit counter.');
		}
		return ['attempts' => $attempts, 'reset_at' => $state['reset_at']];
	}

	protected function hitViaFile(string $safeKey, int $decaySeconds): array
	{
		return $this->withFileLock($safeKey, function ($fp) use ($decaySeconds): array {
			$now = time(); // Read the clock after acquiring the lock.
			$raw = stream_get_contents($fp);
			if ($raw === false) {
				throw new RuntimeException('Cannot read rate limit file.');
			}
			$state = ['attempts' => 0, 'reset_at' => $now + $decaySeconds];
			if ($raw !== '') {
				$decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
				if (!is_array($decoded) || !isset($decoded['attempts'], $decoded['reset_at'])
					|| !is_int($decoded['attempts']) || $decoded['attempts'] < 0 || !is_int($decoded['reset_at'])) {
					throw new RuntimeException('Invalid rate limit file.');
				}
				if ($decoded['reset_at'] > $now) {
					$state = $decoded;
				}
			}
			$state['attempts']++;
			$this->writeState($fp, $state);
			return $state;
		});
	}

	protected function withFileLock(string $safeKey, callable $callback)
	{
		if (!is_dir($this->storageDir) && !@mkdir($this->storageDir, 0755, true) && !is_dir($this->storageDir)) {
			throw new RuntimeException('Cannot create rate limit directory.');
		}
		$fp = @fopen($this->storageDir . DIRECTORY_SEPARATOR . $safeKey . '.json', 'c+');
		if ($fp === false) {
			throw new RuntimeException('Cannot open rate limit file.');
		}
		try {
			if (!flock($fp, LOCK_EX)) {
				throw new RuntimeException('Cannot lock rate limit file.');
			}
			return $callback($fp);
		} finally {
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}

	protected function writeState($fp, array $state): void
	{
		$json = json_encode($state, JSON_THROW_ON_ERROR);
		$length = strlen($json);

		// Overwrite before truncating so a failed write is not mistaken for a new counter.
		if (!rewind($fp)) {
			throw new RuntimeException('Cannot persist rate limit counter.');
		}

		if (fwrite($fp, $json) !== $length) {
			throw new RuntimeException('Cannot persist rate limit counter.');
		}

		if (!ftruncate($fp, $length)) {
			throw new RuntimeException('Cannot persist rate limit counter.');
		}

		if (!fflush($fp)) {
			throw new RuntimeException('Cannot persist rate limit counter.');
		}
	}

	protected function getAtomicCache(): object
	{
		return new AtomicRateLimitCache($this->driver);
	}
}
