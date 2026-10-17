<?php

require dirname(__DIR__) . '/bootstrap.php';

$limiter = new App\Services\RateLimiterService('file', $argv[1]);
while (!is_file($argv[2])) {
	usleep(1000);
}
$allowed = 0;
for ($i = 0; $i < 20; $i++) {
	try {
		$limiter->attempt('parallel', 10, 60);
		$allowed++;
	} catch (App\Exceptions\TooManyRequestsException $e) {
		// Expected once the shared limit is reached.
	}
}
echo $allowed;
