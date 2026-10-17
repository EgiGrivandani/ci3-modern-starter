<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI Database Migration Runner
 *
 * Usage from project root:
 *   php public/index.php migrate                # Migrate to latest version
 *   php public/index.php migrate version <ver>  # Migrate/rollback to specific version (0 to reset)
 */
#[\AllowDynamicProperties]
class Migrate extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		if (!is_cli()) {
			show_404();
		}

		if (!isset($this->db)) {
			$this->load->database();
		}

		$this->load->library('migration');
	}

	/**
	 * Run all pending migrations up to the latest timestamp/sequential version.
	 *
	 * @return void
	 */
	public function index(): void
	{
		$result = $this->migration->latest();

		if ($result === false) {
			fwrite(STDERR, '[ERROR] ' . $this->migration->error_string() . PHP_EOL);
			exit(1);
		}

		if ($result === true) {
			fwrite(STDOUT, '[OK] Nothing to migrate. Database is already up to date.' . PHP_EOL);
			return;
		}

		fwrite(STDOUT, '[OK] Migrated successfully to version: ' . $result . PHP_EOL);
	}

	/**
	 * Migrate or rollback to a specific migration version (pass 0 to rollback all).
	 *
	 * @param string $targetVersion
	 * @return void
	 */
	public function version(string $targetVersion = '0'): void
	{
		$result = $this->migration->version($targetVersion);

		if ($result === false) {
			fwrite(STDERR, '[ERROR] ' . $this->migration->error_string() . PHP_EOL);
			exit(1);
		}

		fwrite(STDOUT, '[OK] Database migrated to version: ' . $targetVersion . PHP_EOL);
	}
}
