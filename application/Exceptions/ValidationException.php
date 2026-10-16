<?php

namespace App\Exceptions;

/**
 * Exception thrown when input or domain validation fails (HTTP 422).
 */
class ValidationException extends AppException
{
	/**
	 * @param array<string, string> $errors
	 * @param string                $message
	 */
	public function __construct(array $errors, string $message = 'The given data was invalid.')
	{
		parent::__construct($message, 422, 'VALIDATION_ERROR', $errors);
	}
}
