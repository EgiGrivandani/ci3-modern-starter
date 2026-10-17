<?php

defined('BASEPATH') or exit('No direct script access allowed');

use App\Services\AppInfoService;

class Welcome extends Public_Controller
{
	/**
	 * Starter template welcome page wrapped in layouts/main.php
	 */
	public function index(): void
	{
		$infoService = new AppInfoService();

		$this->renderLayout('welcome_message', [
			'title' => env('APP_NAME', 'CI3 Modern Starter') . ' — Starter Template',
			'info'  => $infoService->getSummary(),
		]);
	}

	/**
	 * Sample JSON API endpoint (GET /api/health)
	 */
	public function health(): void
	{
		$infoService = new AppInfoService();

		$this->success(
			$infoService->getSummary(),
			'CodeIgniter 3 Starter Template is running smoothly on PHP ' . PHP_VERSION
		);
	}
}
