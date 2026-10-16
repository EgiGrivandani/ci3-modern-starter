<?php

namespace App\Exceptions;

/**
 * Exception thrown when a requested domain resource is not found (HTTP 404).
 */
class NotFoundException extends AppException
{
	public function __construct(string $message = 'The requested resource was not found.')
	{
		parent::__construct($message, 404, 'RESOURCE_NOT_FOUND');
	}
}
