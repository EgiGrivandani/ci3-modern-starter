<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TransactionFailureTest extends TestCase
{
	public function test_begin_failure_does_not_execute_callback(): void
	{
		$db = new TransactionDatabaseDouble();
		$db->begin = false;
		$model = new \MY_Model();
		$model->db = $db;
		try {
			$model->transaction(function () { $this->fail('Callback must not run.'); });
			$this->fail('Expected begin failure.');
		} catch (\RuntimeException $e) {
			$this->assertStringContainsString('begin', $e->getMessage());
		}
		$this->assertSame(0, $db->rollbacks);
		$this->assertTrue($db->db_debug);
	}

	public function test_commit_failure_rolls_back_once_and_throws(): void
	{
		$db = new TransactionDatabaseDouble();
		$db->commit = false;
		$model = new \MY_Model();
		$model->db = $db;
		try {
			$model->transaction(function () { return 'must not report success'; });
			$this->fail('Expected commit failure.');
		} catch (\RuntimeException $e) {
			$this->assertStringContainsString('commit', $e->getMessage());
		}
		$this->assertSame(1, $db->rollbacks);
		$this->assertTrue($db->db_debug);
	}

	public function test_nested_transaction_rejects_before_running_inner_callback(): void
	{
		$db = new TransactionDatabaseDouble();
		$model = new \MY_Model();
		$model->db = $db;
		try {
			$model->transaction(function () use ($model) {
				$model->transaction(function () { $this->fail('Inner callback must not run.'); });
			});
			$this->fail('Expected nested transaction rejection.');
		} catch (\LogicException $e) {
			$this->assertStringContainsString('Nested', $e->getMessage());
		}
		$this->assertSame(1, $db->rollbacks);
		$this->assertFalse($db->active);
	}

	public function test_rollback_failure_preserves_original_exception(): void
	{
		$db = new TransactionDatabaseDouble();
		$db->rollback = false;
		$model = new \MY_Model();
		$model->db = $db;
		$original = new \RuntimeException('Business operation failed.');
		try {
			$model->transaction(function () use ($original) { throw $original; });
			$this->fail('Expected rollback failure.');
		} catch (\RuntimeException $e) {
			$this->assertSame($original, $e->getPrevious());
			$this->assertStringContainsString('rollback could not be confirmed', $e->getMessage());
		}
		$this->assertSame(1, $db->rollbacks);
		$this->assertTrue($db->db_debug);
	}
}

class TransactionDatabaseDouble
{
	public bool $db_debug = true;
	public bool $begin = true;
	public bool $commit = true;
	public bool $rollback = true;
	public bool $active = false;
	public int $rollbacks = 0;
	public function trans_active(): bool { return $this->active; }
	public function trans_begin(): bool { return $this->active = $this->begin; }
	public function trans_status(): bool { return true; }
	public function trans_commit(): bool { if ($this->commit) $this->active = false; return $this->commit; }
	public function trans_rollback(): bool { $this->rollbacks++; $this->active = false; return $this->rollback; }
}
