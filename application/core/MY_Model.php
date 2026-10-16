<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base Model with Eloquent-like CRUD, Pagination & Timestamps support
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
	 * Get all records matching optional conditions.
	 *
	 * @param array<string, mixed> $conditions
	 * @param string|null          $orderBy
	 * @return array<int, object>
	 */
	public function all(array $conditions = [], ?string $orderBy = null): array
	{
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
	 * @param int|string $id
	 * @return object|null
	 */
	public function find($id): ?object
	{
		return $this->db
			->where($this->primaryKey, $id)
			->limit(1)
			->get($this->table)
			->row();
	}

	/**
	 * Find a single record by a specific column and value.
	 *
	 * @param string $column
	 * @param mixed  $value
	 * @return object|null
	 */
	public function findBy(string $column, $value): ?object
	{
		return $this->db
			->where($column, $value)
			->limit(1)
			->get($this->table)
			->row();
	}

	/**
	 * Simple, clean pagination helper (like Laravel's paginate()).
	 *
	 * @param int                  $perPage
	 * @param int                  $page
	 * @param array<string, mixed> $conditions
	 * @param string|null          $orderBy
	 * @return array{data: array<int, object>, total: int, per_page: int, current_page: int, last_page: int}
	 */
	public function paginate(int $perPage = 10, int $page = 1, array $conditions = [], ?string $orderBy = null): array
	{
		$page    = max(1, $page);
		$perPage = max(1, $perPage);
		$total   = $this->count($conditions);
		$offset  = ($page - 1) * $perPage;

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

		return (bool) $this->db
			->where($this->primaryKey, $id)
			->update($this->table, $payload);
	}

	/**
	 * Delete a record by primary key.
	 *
	 * @param int|string $id
	 * @return bool
	 */
	public function delete($id): bool
	{
		return (bool) $this->db
			->where($this->primaryKey, $id)
			->delete($this->table);
	}

	/**
	 * Count records matching conditions.
	 *
	 * @param array<string, mixed> $conditions
	 * @return int
	 */
	public function count(array $conditions = []): int
	{
		if (!empty($conditions)) {
			$this->db->where($conditions);
		}

		return (int) $this->db->count_all_results($this->table);
	}

	/**
	 * Filter data against $this->fillable if defined.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	protected function filterFillable(array $data): array
	{
		if (empty($this->fillable)) {
			return $data;
		}

		return array_intersect_key($data, array_flip($this->fillable));
	}
}
