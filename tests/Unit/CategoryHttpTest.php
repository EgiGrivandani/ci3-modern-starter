<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CategoryHttpTest extends TestCase
{
	private $server;
	private string $directory;
	private string $baseUrl;
	private array $cookies = [];
	private array $environment;

	protected function setUp(): void
	{
		if (!extension_loaded('sqlite3') || !function_exists('proc_open')) {
			$this->markTestSkipped('SQLite3 and proc_open are required.');
		}
		$this->directory = sys_get_temp_dir() . '/ci3-category-' . bin2hex(random_bytes(8));
		mkdir($this->directory);
		$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
		$this->assertIsResource($socket, $error);
		$address = stream_socket_get_name($socket, false);
		fclose($socket);
		$this->baseUrl = 'http://' . $address . '/';
		$this->environment = array_merge(getenv(), [
			'APP_ENV' => 'testing', 'APP_URL' => $this->baseUrl,
			'DB_CONNECTION' => 'sqlite3', 'DB_DATABASE' => $this->directory . '/test.sqlite',
			'DB_PASSWORD' => '', 'DB_DSN' => '', 'DB_PREFIX' => '',
			'CSRF_PROTECTION' => 'true', 'COOKIE_SECURE' => 'false',
		]);
		$this->migrate([]);
		$root = dirname(__DIR__, 2);
		$this->server = proc_open(
			[PHP_BINARY, '-S', $address, '-t', $root . '/public', $root . '/tests/fixtures/http_router.php'],
			[0 => ['pipe', 'r'], 1 => ['file', $this->directory . '/server.log', 'a'], 2 => ['file', $this->directory . '/server.log', 'a']],
			$pipes,
			$root . '/public',
			$this->environment
		);
		fclose($pipes[0]);
		$ready = false;
		for ($i = 0; $i < 100; $i++) {
			$probe = @stream_socket_client('tcp://' . $address, $errno, $error, .1);
			if ($probe) {
				fclose($probe);
				$ready = true;
				break;
			}
			usleep(20000);
		}
		$this->assertTrue($ready, 'Test HTTP server did not start.');
	}

	protected function tearDown(): void
	{
		if (is_resource($this->server)) {
			proc_terminate($this->server);
			proc_close($this->server);
		}
		if (isset($this->directory)) {
			foreach (glob($this->directory . '/*') as $file) {
				unlink($file);
			}
			rmdir($this->directory);
		}
	}

	private function migrate(array $arguments): void
	{
		$process = proc_open(
			array_merge([PHP_BINARY, 'public/index.php', 'migrate'], $arguments),
			[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
			$pipes,
			dirname(__DIR__, 2),
			$this->environment
		);
		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$this->assertSame(0, proc_close($process), $output);
		$this->assertStringContainsString('[OK]', $output);
	}

	private function request(string $path, ?array $data = null): array
	{
		$headers = ['Cookie: ' . implode('; ', array_map(fn ($name, $value) => $name . '=' . $value, array_keys($this->cookies), $this->cookies))];
		if ($data !== null) {
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		}
		$context = stream_context_create(['http' => [
			'method' => $data === null ? 'GET' : 'POST', 'header' => implode("\r\n", $headers),
			'content' => $data === null ? '' : http_build_query($data),
			'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 5,
		]]);
		$stream = fopen($this->baseUrl . $path, 'r', false, $context);
		$this->assertIsResource($stream);
		$body = stream_get_contents($stream);
		$responseHeaders = stream_get_meta_data($stream)['wrapper_data'];
		fclose($stream);
		foreach ($responseHeaders as $header) {
			if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $header, $match)) {
				$this->cookies[$match[1]] = $match[2];
			}
		}
		preg_match('/HTTP\/\S+ (\d+)/', $responseHeaders[0], $match);
		return [(int) $match[1], $body];
	}

	private function token(string $html): string
	{
		$this->assertSame(1, preg_match('/name="csrf_token" value="([^"]+)"/', $html, $match));
		return $match[1];
	}

	public function test_category_crud_forms_csrf_pagination_and_migration_rollback(): void
	{
		[$status, $body] = $this->request('categories');
		$this->assertSame(200, $status, $body);
		$this->assertStringContainsString('Belum ada kategori', $body);
		[$status] = $this->request('categories/create', ['name' => 'No token']);
		$this->assertSame(403, $status);
		[, $form] = $this->request('categories/create');
		[$status, $form] = $this->request('categories/create', ['name' => '  ', 'csrf_token' => $this->token($form)]);
		$this->assertSame(422, $status, $form);
		[$status] = $this->request('categories/create', ['name' => '  Buku  ', 'description' => '<script>alert(1)</script>', 'id' => 999, 'csrf_token' => $this->token($form)]);
		$this->assertSame(303, $status);
		$db = new \SQLite3($this->directory . '/test.sqlite');
		$this->assertSame('Buku', $db->querySingle('SELECT name FROM categories WHERE id=1'));
		[$status, $body] = $this->request('categories');
		$this->assertSame(200, $status);
		$this->assertStringContainsString('&lt;script&gt;', $body);
		$this->assertStringNotContainsString('<script>alert(1)</script>', $body);
		[, $form] = $this->request('categories/edit/1');
		[$status] = $this->request('categories/edit/1', ['name' => '  Majalah  ', 'description' => '', 'csrf_token' => $this->token($form)]);
		$this->assertSame(303, $status);
		$this->assertSame('Majalah', $db->querySingle('SELECT name FROM categories WHERE id=1'));
		[$status] = $this->request('categories/delete/1');
		$this->assertSame(405, $status);
		[$status] = $this->request('categories/edit/99999');
		$this->assertSame(404, $status);
		for ($i = 0; $i < 11; $i++) {
			$db->exec("INSERT INTO categories (name, description, created_at, updated_at) VALUES ('Sample', '', '2026-10-17', '2026-10-17')");
		}
		[, $body] = $this->request('categories?page=2');
		$this->assertStringContainsString('Halaman 2', $body);
		$this->assertStringContainsString('Majalah', $body);
		[$status] = $this->request('categories/delete/1', ['csrf_token' => $this->token($body)]);
		$this->assertSame(303, $status);
		$this->assertSame(0, $db->querySingle('SELECT COUNT(*) FROM categories WHERE id=1'));
		$this->migrate(['version', '0']);
		$this->assertSame(0, $db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='categories'"));
		$db->close();
	}

	public function test_example_is_disabled_in_production_before_database_access(): void
	{
		$path = $this->directory . '/must-not-be-created.sqlite';
		$environment = array_merge($this->environment, ['APP_ENV' => 'production', 'DB_DATABASE' => $path]);
		$process = proc_open(
			[PHP_BINARY, 'public/index.php', 'categories'],
			[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
			$pipes,
			dirname(__DIR__, 2),
			$environment
		);
		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$this->assertNotSame(0, proc_close($process));
		$this->assertStringContainsString('Not Found', $output);
		$this->assertFileDoesNotExist($path);
	}
}
