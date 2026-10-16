<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= e($title ?? $app_name ?? 'CodeIgniter 3') ?></title>
	<style>
		:root {
			--bg: #0f172a;
			--card: #1e293b;
			--border: #334155;
			--text: #f8fafc;
			--muted: #94a3b8;
			--accent: #f97316;
			--accent-hover: #ea580c;
			--success: #22c55e;
			--code-bg: #090d16;
		}
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			background-color: var(--bg);
			color: var(--text);
			font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			line-height: 1.6;
			padding: 2rem 1rem;
		}
		.container {
			max-width: 1040px;
			margin: 0 auto;
		}
		header {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			justify-content: space-between;
			gap: 1rem;
			padding-bottom: 1.5rem;
			margin-bottom: 2rem;
			border-bottom: 1px solid var(--border);
		}
		.brand {
			display: flex;
			align-items: center;
			gap: 0.75rem;
		}
		.brand-badge {
			background: var(--accent);
			color: #fff;
			font-weight: 700;
			padding: 0.35rem 0.75rem;
			border-radius: 8px;
			font-size: 0.9rem;
		}
		.brand h1 {
			font-size: 1.4rem;
			font-weight: 700;
		}
		.status-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			background: rgba(34, 197, 94, 0.12);
			color: #4ade80;
			border: 1px solid rgba(34, 197, 94, 0.3);
			padding: 0.35rem 0.85rem;
			border-radius: 999px;
			font-size: 0.85rem;
			font-weight: 600;
		}
		footer {
			margin-top: 2.5rem;
			padding-top: 1.25rem;
			border-top: 1px solid var(--border);
			color: var(--muted);
			font-size: 0.85rem;
			display: flex;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 0.5rem;
		}
	</style>
</head>
<body>
	<div class="container">
		<header>
			<div class="brand">
				<span class="brand-badge">CI <?= e(CI_VERSION) ?></span>
				<h1><?= e($app_name ?? 'CodeIgniter 3 Starter') ?></h1>
			</div>
			<div class="status-pill">
				<span>●</span>
				<span>PHP <?= e(PHP_VERSION) ?> &bull; <?= e(strtoupper(ENVIRONMENT)) ?></span>
			</div>
		</header>

		<main>
			<?= $content ?? '' ?>
		</main>

		<footer>
			<span>Rendered in <strong>{elapsed_time}</strong>s &bull; Memory <strong>{memory_usage}</strong></span>
			<span>Document Root: <code>public/</code> &bull; Config: <code>.env</code></span>
		</footer>
	</div>
</body>
</html>
