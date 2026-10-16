<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sample User Model extending MY_Model
 *
 * Example usage in a Controller:
 *   $this->load->model('User_model', 'userModel');
 *   $users = $this->userModel->all();
 *   $user  = $this->userModel->find(1);
 *   $id    = $this->userModel->create(['name' => 'Budi', 'email' => 'budi@example.com']);
 */
#[\AllowDynamicProperties]
class User_model extends MY_Model
{
	protected string $table = 'users';
	protected string $primaryKey = 'id';
	protected bool $timestamps = true;

	/**
	 * Columns allowed for mass assignment in create() and update().
	 *
	 * @var array<int, string>
	 */
	protected array $fillable = [
		'name',
		'email',
		'password',
		'role',
		'is_active',
	];

	/**
	 * Custom query example: find an active user by email.
	 *
	 * @param string $email
	 * @return object|null
	 */
	public function findActiveByEmail(string $email): ?object
	{
		return $this->db
			->where('email', $email)
			->where('is_active', 1)
			->limit(1)
			->get($this->table)
			->row();
	}
}
