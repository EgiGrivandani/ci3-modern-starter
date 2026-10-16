<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Exceptions\AppException;
use App\Exceptions\TooManyRequestsException;
use App\Exceptions\ValidationException;
use App\Services\RateLimiterService;

/**
 * Base Application Controller (DDIA & Clean Architecture Ready)
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
		$this->data['app_name']   = env('APP_NAME', 'CodeIgniter 3');
		$this->data['request_id'] = request_id();
	}

	/**
	 * Apply standard HTTP security headers and X-Request-Id correlation header to all responses.
	 *
	 * @return void
	 */
	protected function setSecurityHeaders(): void
	{
		$this->output
			->set_header('X-Request-Id: ' . request_id())
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
	 * Send a raw JSON response.
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
	 * Send a standardized JSON success response.
	 *
	 * @param mixed                $data
	 * @param string               $message
	 * @param int                  $statusCode
	 * @param array<string, mixed> $meta
	 * @return void
	 */
	protected function success($data = null, string $message = 'OK', int $statusCode = 200, array $meta = []): void
	{
		$response = [
			'status'  => true,
			'message' => $message,
			'data'    => $data,
		];

		if (!empty($meta)) {
			$response['meta'] = $meta;
		}

		$this->json($response, $statusCode);
	}

	/**
	 * Send a standardized JSON error response with correlation request_id.
	 *
	 * @param string               $message
	 * @param int                  $statusCode
	 * @param array<string, mixed> $errors
	 * @param string               $errorCode
	 * @return void
	 */
	protected function error(
		string $message,
		int $statusCode = 400,
		array $errors = [],
		string $errorCode = 'BAD_REQUEST'
	): void {
		$response = [
			'status'     => false,
			'error_code' => $errorCode,
			'message'    => $message,
			'request_id' => request_id(),
		];

		if (!empty($errors)) {
			$response['errors'] = $errors;
		}

		$this->json($response, $statusCode);
	}

	/**
	 * Handle domain or unexpected exceptions and convert them into standardized JSON error responses.
	 *
	 * @param Throwable $e
	 * @return void
	 */
	protected function handleException(Throwable $e): void
	{
		if ($e instanceof TooManyRequestsException) {
			$this->output->set_header('Retry-After: ' . $e->getRetryAfter());
		}

		if ($e instanceof AppException) {
			$this->error(
				$e->getMessage(),
				$e->getStatusCode(),
				$e->getErrors(),
				$e->getErrorCode()
			);
			return;
		}

		log_context('error', $e->getMessage(), [
			'exception' => get_class($e),
			'file'      => $e->getFile(),
			'line'      => $e->getLine(),
		]);

		$message = (ENVIRONMENT === 'development')
			? $e->getMessage()
			: 'Internal server error. Please contact support with the request_id.';

		$this->error($message, 500, [], 'INTERNAL_SERVER_ERROR');
	}

	/**
	 * Protect an endpoint with Rate Limiting (throttling) by action name and IP / custom identifier.
	 * Uses CACHE_DRIVER from .env ('file', 'redis', 'memcached', 'apcu') with automatic fallback to 'file'.
	 *
	 * Example:
	 *   $this->throttle('login', 5, 60); // Max 5 attempts per 60 seconds
	 *
	 * @param string      $action       Action name (e.g. 'login', 'api_public')
	 * @param int         $maxAttempts  Maximum requests allowed in the window
	 * @param int         $decaySeconds Window duration in seconds
	 * @param string|null $identifier   Custom identifier (defaults to client IP address)
	 * @return void
	 * @throws TooManyRequestsException
	 */
	protected function throttle(
		string $action = 'global',
		int $maxAttempts = 60,
		int $decaySeconds = 60,
		?string $identifier = null
	): void {
		$clientKey = $identifier ?? (function_exists('client_ip') ? client_ip() : (string) $this->input->ip_address());
		$limiter   = new RateLimiterService();

		$this->output->set_header('X-RateLimit-Limit: ' . max(1, $maxAttempts));

		try {
			$result = $limiter->attempt($action . ':' . $clientKey, $maxAttempts, $decaySeconds);
			$this->output->set_header('X-RateLimit-Remaining: ' . $result['remaining']);
		} catch (TooManyRequestsException $e) {
			$this->output
				->set_header('X-RateLimit-Remaining: 0')
				->set_header('Retry-After: ' . $e->getRetryAfter());
			throw $e;
		}
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

	/**
	 * Validate input data (POST or custom/JSON array) against CI3 validation rules.
	 * Throws ValidationException (HTTP 422) on failure, or returns the validated array on success.
	 *
	 * Example:
	 *   $validated = $this->validate([
	 *       'email' => ['label' => 'Email', 'rules' => 'required|valid_email'],
	 *       'name'  => ['label' => 'Nama',  'rules' => 'required|min_length[3]'],
	 *   ], $this->inputJson());
	 *
	 * @param array<string, array{label?: string, rules: string}|string> $rules
	 * @param array<string, mixed>|null                                  $sourceData
	 * @return array<string, mixed>
	 * @throws ValidationException
	 */
	protected function validate(array $rules, ?array $sourceData = null): array
	{
		$this->load->library('form_validation');
		$this->form_validation->reset_validation();

		$input = $sourceData ?? $this->input->post() ?? [];
		if ($sourceData !== null) {
			// Ensure set_data receives a non-empty array or placeholder so CI3 validates $sourceData instead of $_POST
			$this->form_validation->set_data(empty($sourceData) ? ['__empty__' => null] : $sourceData);
		}

		$fields = [];
		foreach ($rules as $field => $config) {
			$fields[] = $field;
			if (is_array($config)) {
				$label     = $config['label'] ?? ucfirst(str_replace('_', ' ', $field));
				$fieldRule = $config['rules'] ?? 'required';
			} else {
				$label     = ucfirst(str_replace('_', ' ', $field));
				$fieldRule = (string) $config;
			}
			$this->form_validation->set_rules($field, $label, $fieldRule);
		}

		if ($this->form_validation->run() === false) {
			$errors = $this->form_validation->error_array();
			throw new ValidationException($errors);
		}

		$validated = [];
		foreach ($fields as $field) {
			$validated[$field] = $input[$field] ?? null;
		}

		return $validated;
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
				$this->error('Unauthenticated.', 401, [], 'UNAUTHENTICATED');
				$this->output->_display();
				exit;
			}
			redirect('login');
		}
	}
}
