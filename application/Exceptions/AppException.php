<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base Application / Domain Exception
 *
 * Designed for clean separation between Service Layer and HTTP Controller Layer.
 */
class AppException extends RuntimeException
{
	protected int $statusCode;
	protected string $errorCode;

	/**
	 * @var array<string, mixed>
	 */
	protected array $errors;

	/**
	 * @param string               $message
	 * @param int                  $statusCode
	 * @param string               $errorCode
	 * @param array<string, mixed> $errors
	 * @param Throwable|null       $previous
	 */
	public function __construct(
		string $message = 'An application error occurred.',
		int $statusCode = 400,
		string $errorCode = 'APP_ERROR',
		array $errors = [],
		?Throwable $previous = null
	) {
		parent::__construct($message, $statusCode, $previous);
		$this->statusCode = $statusCode;
		$this->errorCode  = $errorCode;
		$this->errors     = $errors;
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	public function getErrorCode(): string
	{
		return $this->errorCode;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}
}
