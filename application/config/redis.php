<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Redis Configuration (Used when CACHE_DRIVER=redis in .env)
|--------------------------------------------------------------------------
*/
$config['socket_type'] = env('REDIS_SOCKET_TYPE', 'tcp');
$config['socket']      = env('REDIS_SOCKET', '');
$config['host']        = env('REDIS_HOST', '127.0.0.1');
$config['password']    = env('REDIS_PASSWORD', null);
$config['port']        = (int) env('REDIS_PORT', 6379);
$config['timeout']     = (float) env('REDIS_TIMEOUT', 1.5);
$config['database']    = (int) env('REDIS_DATABASE', 0);
