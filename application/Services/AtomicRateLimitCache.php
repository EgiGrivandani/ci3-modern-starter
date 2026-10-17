<?php

namespace App\Services;

use RuntimeException;

/** Native atomic operations; deliberately independent of CI_Cache's fallback driver. */
class AtomicRateLimitCache
{
	private string $driver;
	private ?object $client = null;

	public function __construct(string $driver)
	{
		$this->driver = $driver;

		if ($driver === 'redis') {
			$this->connectRedis();
			return;
		}

		if ($driver === 'memcached') {
			$this->connectMemcached();
			return;
		}

		if ($driver !== 'apcu' || !function_exists('apcu_enabled') || !apcu_enabled()) {
			throw new RuntimeException('The requested atomic cache is unavailable.');
		}
	}

	private function connectRedis(): void
	{
		if (!class_exists('Redis')) {
			throw new RuntimeException('The Redis extension is unavailable.');
		}

		$host = env('REDIS_HOST', '127.0.0.1');
		$port = (int) env('REDIS_PORT', 6379);
		$timeout = (float) env('REDIS_TIMEOUT', 1.5);
		if (env('REDIS_SOCKET_TYPE', 'tcp') === 'unix') {
			$host = env('REDIS_SOCKET', '');
			$port = 0;
		}

		$this->client = new \Redis();
		if (!$this->client->connect($host, $port, $timeout)) {
			throw new RuntimeException('Cannot connect to Redis.');
		}

		$this->client->setOption(\Redis::OPT_READ_TIMEOUT, $timeout);
		$password = env('REDIS_PASSWORD');
		if ($password !== null && $password !== '') {
			if (!$this->client->auth($password)) {
				throw new RuntimeException('Redis authentication failed.');
			}
		}

		$database = (int) env('REDIS_DATABASE', 0);
		if (!$this->client->select($database)) {
			throw new RuntimeException('Cannot select Redis database.');
		}
	}

	private function connectMemcached(): void
	{
		if (!class_exists('Memcached')) {
			throw new RuntimeException('The Memcached extension is unavailable.');
		}

		$this->client = new \Memcached();
		$config = [];
		require APPPATH . 'config/memcached.php';

		foreach ($config as $server) {
			$host = $server['hostname'];
			$port = (int) $server['port'];
			$weight = (int) $server['weight'];

			if (!$this->client->addServer($host, $port, $weight)) {
				throw new RuntimeException('Cannot configure Memcached server.');
			}
		}
	}

	public function get(string $key)
	{
		if ($this->driver === 'apcu') {
			return apcu_fetch($key);
		}
		$value = $this->client->get($key);
		if ($this->driver === 'memcached' && $value === false && $this->client->getResultCode() !== \Memcached::RES_NOTFOUND) {
			throw new RuntimeException('Memcached read failed.');
		}
		return $value;
	}

	/** Add only if absent. An existing value must never be overwritten. */
	public function add(string $key, $value, int $ttl): bool
	{
		if ($this->driver === 'redis') {
			return $this->client->set($key, $value, ['nx', 'ex' => $ttl]) === true;
		}
		if ($this->driver === 'apcu') {
			return apcu_add($key, $value, $ttl);
		}
		// Memcached interprets expirations longer than 30 days as Unix timestamps.
		$expiration = $ttl;
		if ($ttl > 2592000) {
			$expiration = time() + $ttl;
		}

		$result = $this->client->add($key, $value, $expiration);
		if (!$result && !in_array($this->client->getResultCode(), [\Memcached::RES_NOTSTORED, \Memcached::RES_DATA_EXISTS], true)) {
			throw new RuntimeException('Memcached add failed.');
		}
		return $result;
	}

	public function increment(string $key)
	{
		if ($this->driver === 'redis') {
			// INCR alone recreates an expired key without a TTL. Do not recreate it.
			$script = <<<'LUA'
if redis.call("EXISTS", KEYS[1]) == 0 then
    return false
end
return redis.call("INCR", KEYS[1])
LUA;
			return $this->client->eval($script, [$key], 1);
		}
		if ($this->driver === 'apcu') {
			// apcu_inc can recreate a missing key. CAS preserves the existing TTL
			// and reports eviction/expiry instead of silently restarting the count.
			for ($attempt = 0; $attempt < 100; $attempt++) {
				$value = apcu_fetch($key, $found);
				if (!$found || !is_int($value)) {
					return false;
				}
				if (apcu_cas($key, $value, $value + 1)) {
					return $value + 1;
				}
			}
			return false;
		}
		return $this->client->increment($key);
	}

	public function delete(string $key): void
	{
		if ($this->driver === 'redis') {
			if ($this->client->del($key) === false) {
				throw new RuntimeException('Redis delete failed.');
			}
		} elseif ($this->driver === 'memcached') {
			if (!$this->client->delete($key) && $this->client->getResultCode() !== \Memcached::RES_NOTFOUND) {
				throw new RuntimeException('Memcached delete failed.');
			}
		} else {
			apcu_delete($key);
		}
	}
}
