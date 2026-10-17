<?php

namespace App\Exceptions;

use Throwable;

class RateLimiterUnavailableException extends AppException
{
	public function __construct(?Throwable $previous = null)
	{
		parent::__construct('Request protection is temporarily unavailable. Please try again later.', 503, 'RATE_LIMITER_UNAVAILABLE', [], $previous);
	}
}
