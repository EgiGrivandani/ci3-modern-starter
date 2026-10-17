<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StarterContractTest extends TestCase
{
	private function runPhp(array $arguments): array
	{
		$process = proc_open(array_merge([PHP_BINARY], $arguments), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
		$this->assertIsResource($process);
		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		return [proc_close($process), $output, $error];
	}

	private function scenario(string $name): string
	{
		[$exit, $output, $error] = $this->runPhp(['tests/fixtures/starter_contract.php', $name]);
		$this->assertSame(0, $exit, $error . $output);
		return $output;
	}

	public function test_validation_returns_processed_whitelisted_fields_for_post_and_json(): void
	{
		$this->assertSame(['name' => 'Budi', 'optional' => null], json_decode($this->scenario('validate_post'), true));
		$this->assertSame(['name' => 'Sari', 'optional' => null], json_decode($this->scenario('validate_json'), true));
	}

	public function test_empty_json_does_not_fall_back_to_post(): void
	{
		$result = json_decode($this->scenario('validate_empty'), true);
		$this->assertSame('App\\Exceptions\\ValidationException', $result['exception']);
	}

	public function test_nested_fields_are_rejected_explicitly(): void
	{
		$result = json_decode($this->scenario('validate_nested'), true);
		$this->assertSame('InvalidArgumentException', $result['exception']);
	}

	public function test_real_sqlite_transactions_commit_and_roll_back(): void
	{
		if (!extension_loaded('sqlite3')) {
			$this->markTestSkipped('SQLite3 is required.');
		}
		$result = json_decode($this->scenario('transaction'), true);
		$this->assertSame([['id' => 1]], $result['rows']);
		$this->assertTrue($result['debug']);
		$this->assertFalse($result['active']);
	}

	public function test_api_post_requires_matching_csrf_token(): void
	{
		$this->assertSame('rejected', $this->scenario('csrf_missing'));
		$this->assertSame('accepted', $this->scenario('csrf_valid'));
	}

	public function test_missing_or_invalid_migration_version_fails_before_database_access(): void
	{
		foreach ([[], ['abc'], ['-1'], ['1.5'], ['00'], ['999999999999999999999'], ['0', 'extra']] as $args) {
			[$exit, $output, $error] = $this->runPhp(array_merge(['public/index.php', 'migrate', 'version'], $args));
			$this->assertSame(1, $exit, $output . $error);
			$this->assertStringContainsString('Usage:', $error);
			$this->assertStringNotContainsString('Database Error', $output . $error);
		}
	}

	public function test_explicit_zero_is_forwarded_to_migration_runner(): void
	{
		$this->assertStringContainsString('TARGET=0', $this->scenario('migrate_zero'));
	}
}
