<?php
/**
 * PHPUnit Bootstrap for CodeIgniter 3 Modern Starter
 *
 * Initializes Composer autoloader, .env variables, and core CI3 constants
 * so PSR-4 Services, Models, Exceptions, and Helpers can be unit-tested cleanly.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

if (!defined('ENVIRONMENT')) {
	define('ENVIRONMENT', 'testing');
}

if (!defined('BASEPATH')) {
	define('BASEPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
}

if (!defined('APPPATH')) {
	define('APPPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
}

if (!defined('VIEWPATH')) {
	define('VIEWPATH', APPPATH . 'views' . DIRECTORY_SEPARATOR);
}

if (!defined('CI_VERSION')) {
	define('CI_VERSION', '3.4.5');
}

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

// Load CI_Model and MY_Model for unit testing model logic without full HTTP bootstrap
if (!class_exists('CI_Model')) {
	require_once BASEPATH . 'core/Model.php';
}
if (!class_exists('MY_Model')) {
	require_once APPPATH . 'core/MY_Model.php';
}
