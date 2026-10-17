<?php

namespace App\Exceptions;

/**
 * Exception thrown when authentication is required or has failed (HTTP 401).
 */
class UnauthorizedException extends AppException
{
	public function __construct(string $message = 'Unauthenticated.')
	{
		parent::__construct($message, 401, 'UNAUTHORIZED');
	}
}
