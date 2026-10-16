<?php

namespace App\Exceptions;

/**
 * Exception thrown when a client exceeds the allowed rate limit (HTTP 429).
 */
class TooManyRequestsException extends AppException
{
	protected int $retryAfter;

	public function __construct(
		int $retryAfter = 60,
		string $message = 'Too many requests. Please try again later.'
	) {
		parent::__construct($message, 429, 'TOO_MANY_REQUESTS', [
			'retry_after_seconds' => $retryAfter,
		]);
		$this->retryAfter = $retryAfter;
	}

	public function getRetryAfter(): int
	{
		return $this->retryAfter;
	}
}
