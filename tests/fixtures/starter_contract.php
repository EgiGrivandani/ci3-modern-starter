<?php

// Boot the actual framework without touching the configured application database.
$scenario = $argv[1];
$_SERVER['argv'] = $argv = [$argv[0], 'welcome'];
ob_start();
require dirname(__DIR__, 2) . '/public/index.php';
ob_end_clean();
$ci = & get_instance();

if (str_starts_with($scenario, 'validate')) {
	$method = new ReflectionMethod(MY_Controller::class, 'validate');
	$method->setAccessible(true);
	$_POST = ['name' => '  Budi  ', 'role' => 'admin'];
	try {
		if ($scenario === 'validate_nested') {
			$result = $method->invoke($ci, ['user[email]' => 'required'], ['user' => ['email' => 'a@example.com']]);
		} else {
			$source = $scenario === 'validate_post' ? null : ($scenario === 'validate_empty' ? [] : ['name' => '  Sari  ', 'role' => 'admin']);
			$result = $method->invoke($ci, ['name' => 'trim|required', 'optional' => 'trim'], $source);
		}
		echo json_encode($result);
	} catch (Throwable $e) {
		echo json_encode(['exception' => get_class($e), 'message' => $e->getMessage()]);
	}
} elseif (str_starts_with($scenario, 'csrf')) {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$ci->uri->uri_string = 'api/produk';
	$security = new class () extends CI_Security {
		public function __construct()
		{
			$this->_csrf_token_name = config_item('csrf_token_name');
			$this->_csrf_cookie_name = config_item('csrf_cookie_name');
		}
		public function csrf_set_cookie()
		{
			return $this;
		}
		public function csrf_show_error()
		{
			throw new RuntimeException('CSRF rejected');
		}
	};
	$_POST = $scenario === 'csrf_valid' ? [config_item('csrf_token_name') => str_repeat('a', 32)] : [];
	$_COOKIE = [config_item('csrf_cookie_name') => str_repeat('a', 32)];
	try {
		$security->csrf_verify();
		echo 'accepted';
	} catch (RuntimeException $e) {
		echo 'rejected';
	}
} elseif ($scenario === 'transaction') {
	$ci->load->database(['dbdriver' => 'sqlite3', 'database' => ':memory:', 'db_debug' => true]);
	$ci->load->model('User_model');
	$db = $ci->db;
	$db->query('CREATE TABLE records (id INTEGER PRIMARY KEY, name TEXT)');
	$ci->User_model->transaction(function () use ($db) {
		$db->query("INSERT INTO records VALUES (1, 'committed')");
	});
	try {
		$ci->User_model->transaction(function () use ($db) {
			$db->query("INSERT INTO records VALUES (2, 'rolled back')");
			throw new RuntimeException('Abort');
		});
	} catch (RuntimeException $e) {
	}
	try {
		$ci->User_model->transaction(function () use ($db) {
			$db->query("INSERT INTO records VALUES (3, 'also rolled back')");
			// SQLite raises a PHP warning on invalid SQL; suppress only that warning.
			@$db->query('INSERT INTO missing_table VALUES (1)');
		});
	} catch (RuntimeException $e) {
	}
	echo json_encode(['rows' => $db->query('SELECT id FROM records')->result_array(), 'debug' => $db->db_debug, 'active' => $db->trans_active()]);
} elseif ($scenario === 'migrate_zero') {
	require APPPATH . 'controllers/Migrate.php';
	$runner = new class () extends Migrate {
		public function __construct()
		{
		}
		protected function initializeMigration(): void
		{
			$this->migration = new class () {
				public function version($version)
				{
					echo 'TARGET=' . $version . PHP_EOL;
					return true;
				}
			};
		}
	};
	$runner->version('0');
}
