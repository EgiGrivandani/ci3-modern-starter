<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base Application Controller
 */
#[\AllowDynamicProperties]
class MY_Controller extends CI_Controller
{
	/**
	 * Shared data passed to views.
	 *
	 * @var array<string, mixed>
	 */
	protected array $data = [];

	/**
	 * Default master layout view path.
	 *
	 * @var string
	 */
	protected string $layout = 'layouts/main';

	public function __construct()
	{
		parent::__construct();
		$this->setSecurityHeaders();
		$this->data['app_name'] = env('APP_NAME', 'CodeIgniter 3');
	}

	/**
	 * Apply standard HTTP security headers to all responses.
	 *
	 * @return void
	 */
	protected function setSecurityHeaders(): void
	{
		$this->output
			->set_header('X-Frame-Options: SAMEORIGIN')
			->set_header('X-Content-Type-Options: nosniff')
			->set_header('Referrer-Policy: strict-origin-when-cross-origin')
			->set_header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
	}

	/**
	 * Render a standalone view with shared $this->data.
	 *
	 * @param string $view
	 * @param array  $data
	 * @param bool   $return
	 * @return string|void
	 */
	protected function render(string $view, array $data = [], bool $return = false)
	{
		$viewData = array_merge($this->data, $data);
		return $this->load->view($view, $viewData, $return);
	}

	/**
	 * Render a view wrapped inside a master layout (Laravel-style layout).
	 *
	 * @param string      $view
	 * @param array       $data
	 * @param string|null $layout
	 * @return void
	 */
	protected function renderLayout(string $view, array $data = [], ?string $layout = null): void
	{
		$viewData = array_merge($this->data, $data);
		$viewData['content'] = $this->load->view($view, $viewData, true);
		$this->load->view($layout ?? $this->layout, $viewData);
	}

	/**
	 * Send a JSON response (Laravel-style response()->json()).
	 *
	 * @param mixed $payload
	 * @param int   $statusCode
	 * @return void
	 */
	protected function json($payload, int $statusCode = 200): void
	{
		$this->output
			->set_status_header($statusCode)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	/**
	 * Parse JSON request body into an associative array.
	 *
	 * @return array<string, mixed>
	 */
	protected function inputJson(): array
	{
		$raw = $this->input->raw_input_stream;
		if (empty($raw)) {
			return [];
		}

		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : [];
	}
}

/**
 * Base Controller for Public Pages
 */
#[\AllowDynamicProperties]
class Public_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
}

/**
 * Base Controller for Authenticated Pages
 */
#[\AllowDynamicProperties]
class Auth_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		if (!$this->session->userdata('user_id')) {
			if ($this->input->is_ajax_request()) {
				$this->json(['message' => 'Unauthenticated.'], 401);
				$this->output->_display();
				exit;
			}
			redirect('login');
		}
	}
}
