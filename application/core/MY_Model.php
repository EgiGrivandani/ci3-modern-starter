<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Base Model with Eloquent-like CRUD, Safe Pagination, Transactions,
 * Pessimistic Locking, Soft Deletes & Timestamps support (DDIA Ready).
 */
#[\AllowDynamicProperties]
class MY_Model extends CI_Model
{
	/**
	 * Database table name.
	 *
	 * @var string
	 */
	protected string $table = '';

	/**
	 * Primary key column.
	 *
	 * @var string
	 */
	protected string $primaryKey = 'id';

	/**
	 * Automatically manage created_at and updated_at columns.
	 *
	 * @var bool
	 */
	protected bool $timestamps = true;

	/**
	 * Enable soft deletes using deleted_at column instead of physical DELETE.
	 *
	 * @var bool
	 */
	protected bool $softDeletes = false;

	/**
	 * Column name used for soft deletes.
	 *
	 * @var string
	 */
	protected string $deletedAtColumn = 'deleted_at';

	/**
	 * Maximum allowed items per page in paginate() to protect database scalability.
	 *
	 * @var int
	 */
	protected int $maxPerPage = 100;

	/**
	 * Whitelist of fillable columns (empty array allows all keys).
	 *
	 * @var array<int, string>
	 */
	protected array $fillable = [];

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute a callback inside an atomic database transaction.
	 * Automatically commits on success or rolls back on query failure / exception.
	 * Nested transactions are rejected; the outer service owns the transaction.
	 *
	 * @param callable $callback
	 * @return mixed
	 * @throws Throwable
	 */
	public function transaction(callable $callback)
	{
		if ($this->db->trans_active()) {
			throw new LogicException('Nested transactions are not supported. Use the existing outer transaction.');
		}
		// CI's debug mode can terminate execution on a query error, bypassing catch/finally.
		$debug = $this->db->db_debug;
		$this->db->db_debug = false;
		try {
			if (!$this->db->trans_begin()) {
				throw new RuntimeException('Could not begin database transaction.');
			}
			try {
				$result = $callback($this);
				if ($this->db->trans_status() === false) {
					throw new RuntimeException('Database transaction failed.');
				}
				if (!$this->db->trans_commit()) {
					throw new RuntimeException('Could not commit database transaction.');
				}
				return $result;
			} catch (Throwable $e) {
				try {
					if (!$this->db->trans_rollback()) {
						throw new RuntimeException('Rollback returned false.');
					}
				} catch (Throwable $rollbackError) {
					log_context('error', 'Database rollback failed.', ['error' => $rollbackError->getMessage()]);
					throw new RuntimeException('Database transaction failed and rollback could not be confirmed.', 0, $e);
				}
				throw $e;
			}
		} finally {
			$this->db->db_debug = $debug;
		}
	}

	/**
	 * Get all records matching optional conditions.
	 *
	 * @param array<string, mixed>      $conditions
	 * @param string|null               $orderBy
	 * @param array<int, string>|string $columns
	 * @return array<int, object>
	 */
	public function all(array $conditions = [], ?string $orderBy = null, $columns = '*'): array
	{
		$this->applySelect($columns);
		$this->applySoftDeleteScope();

		if (!empty($conditions)) {
			$this->db->where($conditions);
		}
		if ($orderBy !== null) {
			$this->db->order_by($orderBy);
		}

		return $this->db->get($this->table)->result();
	}

	/**
	 * Find a single record by primary key.
	 *
	 * @param int|string                $id
	 * @param array<int, string>|string $columns
	 * @return object|null
	 */
	public function find($id, $columns = '*'): ?object
	{
		$this->applySelect($columns);
		$this->applySoftDeleteScope();

		return $this->db
			->where($this->primaryKey, $id)
			->limit(1)
			->get($this->table)
			->row();
	}

	/**
	 * Find a single record by primary key with a pessimistic row lock (SELECT ... FOR UPDATE).
	 * Must be called inside a $this->transaction() block to prevent race conditions.
	 *
	 * @param int|string $id
	 * @return object|null
	 */
	public function findForUpdate($id): ?object
	{
		$table = $this->db->protect_identifiers($this->table, true);
		$pk    = $this->db->protect_identifiers($this->primaryKey);
		$sql   = "SELECT * FROM {$table} WHERE {$pk} = ?";
		$binds = [$id];

		if ($this->softDeletes) {
			$delCol = $this->db->protect_identifiers($this->deletedAtColumn);
			$sql   .= " AND {$delCol} IS NULL";
		}

		$sql .= ' LIMIT 1 FOR UPDATE';

		$query = $this->db->query($sql, $binds);
		return $query ? $query->row() : null;
	}

	/**
	 * Find a single record by a specific column and value.
	 *
	 * @param string                    $column
	 * @param mixed                     $value
	 * @param array<int, string>|string $columns
	 * @return object|null
	 */
	public function findBy(string $column, $value, $columns = '*'): ?object
	{
		$this->applySelect($columns);
		$this->applySoftDeleteScope();

		return $this->db
			->where($column, $value)
			->limit(1)
			->get($this->table)
			->row();
	}

	/**
	 * Bounded, scalable pagination helper (clamps $perPage to $this->maxPerPage).
	 *
	 * @param int                       $perPage
	 * @param int                       $page
	 * @param array<string, mixed>      $conditions
	 * @param string|null               $orderBy
	 * @param array<int, string>|string $columns
	 * @return array{data: array<int, object>, total: int, per_page: int, current_page: int, last_page: int}
	 */
	public function paginate(
		int $perPage = 10,
		int $page = 1,
		array $conditions = [],
		?string $orderBy = null,
		$columns = '*'
	): array {
		$page    = max(1, $page);
		$perPage = $this->clampPerPage($perPage);
		$total   = $this->count($conditions);
		$offset  = ($page - 1) * $perPage;

		$this->applySelect($columns);
		$this->applySoftDeleteScope();

		if (!empty($conditions)) {
			$this->db->where($conditions);
		}
		if ($orderBy !== null) {
			$this->db->order_by($orderBy);
		}

		$data = $this->db
			->limit($perPage, $offset)
			->get($this->table)
			->result();

		return [
			'data'         => $data,
			'total'        => $total,
			'per_page'     => $perPage,
			'current_page' => $page,
			'last_page'    => max(1, (int) ceil($total / $perPage)),
		];
	}

	/**
	 * Insert a new record and return its inserted ID (or false on failure).
	 *
	 * @param array<string, mixed> $data
	 * @return int|string|bool
	 */
	public function create(array $data)
	{
		$payload = $this->filterFillable($data);

		if ($this->timestamps) {
			$now = date('Y-m-d H:i:s');
			$payload['created_at'] = $payload['created_at'] ?? $now;
			$payload['updated_at'] = $payload['updated_at'] ?? $now;
		}

		if ($this->db->insert($this->table, $payload)) {
			return $this->db->insert_id() ?: true;
		}

		return false;
	}

	/**
	 * Insert multiple records in a single batch query for scalability.
	 *
	 * @param array<int, array<string, mixed>> $rows
	 * @return int Number of inserted rows
	 */
	public function insertBatch(array $rows): int
	{
		if (empty($rows)) {
			return 0;
		}

		$now   = date('Y-m-d H:i:s');
		$batch = [];

		foreach ($rows as $row) {
			$payload = $this->filterFillable($row);
			if ($this->timestamps) {
				$payload['created_at'] = $payload['created_at'] ?? $now;
				$payload['updated_at'] = $payload['updated_at'] ?? $now;
			}
			$batch[] = $payload;
		}

		$result = $this->db->insert_batch($this->table, $batch);
		return is_int($result) ? $result : 0;
	}

	/**
	 * Update an existing record by primary key.
	 *
	 * @param int|string           $id
	 * @param array<string, mixed> $data
	 * @return bool
	 */
	public function update($id, array $data): bool
	{
		$payload = $this->filterFillable($data);

		if ($this->timestamps) {
			$payload['updated_at'] = date('Y-m-d H:i:s');
		}

		$this->applySoftDeleteScope();

		return (bool) $this->db
			->where($this->primaryKey, $id)
			->update($this->table, $payload);
	}

	/**
	 * Delete a record by primary key (soft-deletes if $this->softDeletes is enabled, unless $force is true).
	 *
	 * @param int|string $id
	 * @param bool       $force
	 * @return bool
	 */
	public function delete($id, bool $force = false): bool
	{
		if ($this->softDeletes && !$force) {
			$payload = [$this->deletedAtColumn => date('Y-m-d H:i:s')];
			if ($this->timestamps) {
				$payload['updated_at'] = date('Y-m-d H:i:s');
			}

			return (bool) $this->db
				->where($this->primaryKey, $id)
				->where($this->deletedAtColumn . ' IS NULL', null, false)
				->update($this->table, $payload);
		}

		return (bool) $this->db
			->where($this->primaryKey, $id)
			->delete($this->table);
	}

	/**
	 * Restore a soft-deleted record by primary key.
	 *
	 * @param int|string $id
	 * @return bool
	 */
	public function restore($id): bool
	{
		if (!$this->softDeletes) {
			return false;
		}

		$payload = [$this->deletedAtColumn => null];
		if ($this->timestamps) {
			$payload['updated_at'] = date('Y-m-d H:i:s');
		}

		return (bool) $this->db
			->where($this->primaryKey, $id)
			->update($this->table, $payload);
	}

	/**
	 * Count records matching conditions.
	 *
	 * @param array<string, mixed> $conditions
	 * @return int
	 */
	public function count(array $conditions = []): int
	{
		$this->applySoftDeleteScope();

		if (!empty($conditions)) {
			$this->db->where($conditions);
		}

		return (int) $this->db->count_all_results($this->table);
	}

	/**
	 * Clamp requested per-page limit between 1 and $this->maxPerPage.
	 *
	 * @param int $perPage
	 * @return int
	 */
	public function clampPerPage(int $perPage): int
	{
		return min(max(1, $perPage), max(1, $this->maxPerPage));
	}

	/**
	 * Filter data against $this->fillable if defined.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public function filterFillable(array $data): array
	{
		if (empty($this->fillable)) {
			return $data;
		}

		return array_intersect_key($data, array_flip($this->fillable));
	}

	/**
	 * Apply column selection to Query Builder.
	 *
	 * @param array<int, string>|string $columns
	 * @return void
	 */
	protected function applySelect($columns): void
	{
		if (is_array($columns)) {
			$this->db->select(implode(', ', $columns));
		} elseif (is_string($columns) && $columns !== '' && $columns !== '*') {
			$this->db->select($columns);
		}
	}

	/**
	 * Apply WHERE deleted_at IS NULL when soft deletes are enabled.
	 *
	 * @return void
	 */
	protected function applySoftDeleteScope(): void
	{
		if ($this->softDeletes) {
			$this->db->where($this->table . '.' . $this->deletedAtColumn . ' IS NULL', null, false);
		}
	}
}
