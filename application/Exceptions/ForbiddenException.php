<?php

namespace App\Exceptions;

/**
 * Exception thrown when the authenticated user lacks permission for an action (HTTP 403).
 */
class ForbiddenException extends AppException
{
	public function __construct(string $message = 'You do not have permission to perform this action.')
	{
		parent::__construct($message, 403, 'FORBIDDEN');
	}
}
