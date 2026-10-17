<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Email Configuration (Loaded automatically when $this->load->library('email'))
|--------------------------------------------------------------------------
|
| Reads SMTP and mailer preferences from .env (with safe modern defaults).
|
*/
$config['useragent']      = (string) env('APP_NAME', 'CodeIgniter');
$config['protocol']       = (string) env('MAIL_MAILER', 'smtp');
$config['mailpath']       = (string) env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail');
$config['smtp_host']      = (string) env('MAIL_HOST', '127.0.0.1');
$config['smtp_user']      = (string) env('MAIL_USERNAME', '');
$config['smtp_pass']      = (string) env('MAIL_PASSWORD', '');
$config['smtp_port']      = (int) env('MAIL_PORT', 2525);
$config['smtp_timeout']   = (int) env('MAIL_TIMEOUT', 10);
$config['smtp_keepalive'] = (bool) env('MAIL_KEEPALIVE', false);
$config['smtp_crypto']    = (string) env('MAIL_ENCRYPTION', 'tls');
$config['wordwrap']       = true;
$config['wrapchars']      = 76;
$config['mailtype']       = (string) env('MAIL_TYPE', 'html');
$config['charset']        = 'utf-8';
$config['validate']       = true;
$config['priority']       = 3;
$config['crlf']           = "\r\n";
$config['newline']        = "\r\n";
$config['bcc_batch_mode'] = false;
$config['bcc_batch_size'] = 200;
$config['dsn']            = false;

/*
|--------------------------------------------------------------------------
| Default Sender Identity (Accessible via config_item('mail_from_*'))
|--------------------------------------------------------------------------
*/
$config['mail_from_address'] = (string) env('MAIL_FROM_ADDRESS', 'hello@example.com');
$config['mail_from_name']    = (string) env('MAIL_FROM_NAME', (string) env('APP_NAME', 'CI3 Modern Starter'));
